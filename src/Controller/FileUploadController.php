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

            $random_num = md5(uniqid());
            // Filename after renaming (string)
            $filename = $random_num . '.' . $file->guessExtension();
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
        //sql query for creating csv table
        $sql= 'CREATE TABLE '.$random_num.' (';
        for($i=0;$i<count($columns); $i++) {
            $sql .= '`' . $columns[$i].'` VARCHAR(50) ';

            if($i < count($columns) - 1)
                $sql .= ',';
        }
        $sql .= ')';

        //sql query for importing data to table from csv
        $insert_sql=<<<eof
        LOAD DATA LOCAL INFILE '$file_full' 
        INTO TABLE $random_num 
        FIELDS TERMINATED BY ',' 
        ENCLOSED BY '"'
        LINES TERMINATED BY '\n'
        IGNORE 1 LINES;
        eof;
        $conn = $entityManager->getRepository(MetaTable::class)->createDynamicTable($sql);
        $conn = $entityManager->getRepository(MetaTable::class)->addDataToTable($insert_sql);
    
        // Save to meta_table in db

        $metaTable = new MetaTable();
        $em = $doctrine->getManager();
        $metaTable->setFilename($filename);
        $metaTable->setFilesize($filesize);
        $metaTable->setColumns($columns);
        $em->persist($metaTable);
        $em->flush();

        return $this->redirectToRoute('app_modify_file', [
            'filename' => (string) $filename,
        ]);
    } 
    // Fetch Column Names from Database to manipulate further
    #[Route('/modify', name:'app_modify_file')]
    public function modify(Request $request, MetaTableRepository $metaTableRepository): Response
    {   
        $filename = $request->get('filename');

        $result = $metaTableRepository->getColumnNames($filename);
        $columns = $result[0]['columns'];
       
        return $this->render('file_upload/modify.html.twig', [
            'columns' => $columns,
            'filename' => $filename,
        ]);

    }

    // Export the modified CSV
    #[Route('/export', name:'app_export')]
    public function export(Request $request,EntityManagerInterface $entityManager): Response
    {    
        
        ob_start();
        $uploads_directory = $this->getParameter('uploads_directory');
        $filename = $request->get('filename');
        $file_full = $uploads_directory . '/' . $filename;

        // Modified Index wise Columns
        $original_column = $request->get('original_cols');
        $renamed_column = $request->get('text');
        // Array of columns from UI
        $list = array($renamed_column); 
        //$newlist=$list;
        $fp = fopen('php://output', 'w');
        foreach ($list as $fields) {
            fputcsv($fp, $fields);
        }
        $search = '.csv' ;
        $trimmed = str_replace($search, '', $filename) ;

        //sql query for fetching modified csv data from table
        $fetch_sql = 'SELECT ';
        for($i=0;$i<count($original_column); $i++) {
            $fetch_sql .= '`' . $original_column[$i] . '`' ;
            if($i < count($original_column) - 1)
                $fetch_sql .= ',';
        }
        $fetch_sql .= ' FROM ' .$trimmed;
     
        $conn = $entityManager->getRepository(MetaTable::class)->getUpdatedcsv($fetch_sql);
      
        $reader = Reader::createFromPath($file_full);
        $reader->setHeaderOffset(0);
        foreach ($conn as $fields) {
            fputcsv($fp, $fields);
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'binary/octet-stream');
        
        // It's gonna output in a testing.csv file
        $response->headers->set('Content-Disposition', 'attachment; filename="testing.csv"');
        return $response;
        ob_clean();
    }
       
}







