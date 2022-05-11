<?php

namespace App\Controller;

use App\Entity\MetaTable;
use App\Repository\MetaTableRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;
use League\Csv\Reader;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Writer;
use Doctrine\ORM\Mapping as ORM;
class FileUploadController extends AbstractController
{
    // Home Page
    #[Route('/', name:'app_homepage')]
    function index(): Response
    {
        return $this->render('file_upload/index.html.twig', [
            'controller_name' => 'FileUploadController',
        ]);
    }

    // Get File from User and Upl;oad it to the Server (Uploads directory)
    #[Route('/upload', name:'app_upload_file')]
    function upload(Request $request, ManagerRegistry $doctrine,EntityManagerInterface $entityManager)
    {
        $entityManager = $doctrine->getManager(); 
        //Get and Upload CSV
        $file = $request->files->get('formFile');
        $uploads_directory = $this->getParameter('uploads_directory');

        // Extract file if it is zip
        if ($file->guessExtension() == 'zip') {
            $zipArchive = new ZipArchive();
            $zipArchive->open($file);
            $stat = $zipArchive->statIndex(0);

            // file1 = Basename = Filename with Extension (string)
            $file1 = basename($stat['name']);  
            
            // $random_num = uniqid(rand(), true);
            $random_num = md5(uniqid());
            // Filename after renaming (string)
            $filename = $random_num . $file1; 
            
            // Upload
            $zipArchive->extractTo($uploads_directory, $file1);
            $zipArchive->close();
            rename($uploads_directory."/".$file1, $uploads_directory."/".$filename);
        } 
        // Move directly if it is CSV
        else if ($file->guessExtension() == 'csv') {
            $filename = md5(uniqid()) . '.' . $file->guessExtension();
            $file->move(
                $uploads_directory,
                $filename
            );
        }
        
        // $file_full = Absolute file path
        $file_full = $uploads_directory . '/' . $filename;
        // Open and extract csv
        $filesize = filesize($file_full); // bytes
        $filesize = round($filesize / 1024, 2);
        if (($handle = fopen($file_full, "r")) !== false) {
            $columns = fgetcsv($handle, 3000, ",");
            fclose($handle);
        }

        
        $sql= 'CREATE TABLE table_name (';
        for($i=0;$i<count($columns); $i++) {
        $sql .= $columns[$i].' VARCHAR(50) ';

        if($i < count($columns) - 1)
            $sql .= ',';
        }
        $sql .= ')';

       // echo $sql; die;
      //  $em = $doctrine->getManager();
      $conn = $entityManager->getRepository(MetaTable::class)->createDynamicTable($sql);
      //$stmt = $conn->prepare($sql);
      //$stmt->execute();
      
       // return $stmt->fetchAll();
        echo $sql;
        die($sql);
      
        }
        // exec(' csvsql --db mysql+mysqldb://root:password@localhost:3306/company --tables newtab2 --insert /home/sakshigoraniya/Desktop/csvs/2gb.csv
        // ', $output);
        // echo $output;
    

        // Save to meta_table in db

        // $metaTable = new MetaTable();
        // $em = $doctrine->getManager();
        // $metaTable->setFilename($filename);
        // $metaTable->setFilesize($filesize);
        // $metaTable->setColumns($columns);
        // $em->persist($metaTable);
        // $em->flush();

        // return $this->redirectToRoute('app_modify_file', [
        //     'filename' => (string) $filename,
        // ]);
    
    // Fetch Column Names from Database to manipulate further
    #[Route('/modify', name:'app_modify_file')]
    public function modify(Request $request, MetaTableRepository $metaTableRepository): Response
    {   
        $filename = $request->get('filename');

        $result = $metaTableRepository->getColumnNames($filename);
        $columns = $result[0]['columns'];
        // die();
        return $this->render('file_upload/modify.html.twig', [
            'columns' => $columns,
            'filename' => $filename,
        ]);
        // return $this->render('file_upload/rough.html.twig');

    }

    // Export the modified CSV
    #[Route('/export', name:'app_export')]
    public function exportt(Request $request): Response
    {    
        ob_start();
        $uploads_directory = $this->getParameter('uploads_directory');
        $filename = $request->get('filename');
        $file_full = $uploads_directory . '/' . $filename;
        // Original Index wise Columns
        $original_cols = $request->get('original_cols');
        // var_dump($original_cols);
        // Modified Index wise Columns
        $column = $request->get('text');
        // var_dump($column);
        // die();
        // Array of columns from UI
        $list = array($column); 
        $fp = fopen('php://output', 'w');
        // Setting Latest Column Headers in new CSV
        // foreach ($list as $fields) {
        //     fputcsv($fp, $fields);
        // }
        $reader = Reader::createFromPath($file_full);
        // $writer = Writer::createFromPath('', 'w+');
        $reader->setHeaderOffset(0);
        // Putting contents from second line of uploaded CSV
        // $records = $reader->getRecords($column);
        $arr = [];
        foreach ($list as $fields) {
            array_push($arr, $fields);
            // fputcsv($fp, $fields);
        }
            //var_dump($fields); // As per new list
            foreach ($fields as $key => $field) {

                dd($arr);
                $records = iterator_to_array($reader->fetchColumnByName($field));
                $arr = $records; 
                // $records = $reader->getRecords();
                // foreach ($records as $i => $data) {
                    // dd($records);
                     //fputcsv($fp, $records);
                // foreach ($records as $i => $data) {
                    // dd($records);
                     //fputcsv($fp, $records); 
                   
                // }
                //var_dump($records);
            }
            // $writer->insertAll($arr);
            // $records = $reader->fetchColumnByOffset($offset);
            // var_dump($records);
            // foreach ($records as $offset => $record) {
            //     var_dump($record);
                // fputcsv($fp,$arr);    
            // }
            dd($arr);
            foreach ($arr as $fields) {
                fputcsv($fp, $fields);
            }
            fclose($fp);
        //   foreach ($records as $offset => $record) {
        //            foreach($column as $col){
        //            //dd($record[$col]);
        //         $arr=array();
        //         array_push($arr,$record[$col]);
        //       // print_r($arr); 
        //     }
        //die();
        // $response = new Response();
        // $response->headers->set('Content-Type', 'binary/octet-stream');
        
        // // It's gonna output in a testing.csv file
        // $response->headers->set('Content-Disposition', 'attachment; filename="testing.csv"');
        // return $response;
        // ob_clean();
        $response = new Response();
        // $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Type', 'binary/octet-stream');
        
        // It's gonna output in a testing.csv file
        $response->headers->set('Content-Disposition', 'attachment; filename="testing.csv"');
        return $response;
        ob_clean();
    }
    
    #[Route('/store', name:'app_store_csv')]
    public function storetodb(ManagerRegistry $doctrine): Response
    {

   
        return new Response("saved");
    }
       
}







