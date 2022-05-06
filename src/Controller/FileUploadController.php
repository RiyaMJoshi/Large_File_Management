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
use League\Csv\Writer;

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
    function upload(Request $request, ManagerRegistry $doctrine)
    {
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
            $columns = fgetcsv($handle, 1000, ",");
            fclose($handle);
        }

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
        // die();
        return $this->render('file_upload/modify.html.twig', [
            'columns' => $columns,
            'filename' => $filename,
        ]);
        // return $this->render('file_upload/rough.html.twig');

    }

    // Export the modified CSV
    #[Route('/export', name:'app_export')]
    public function export(Request $request): Response
    {    
        $uploads_directory = $this->getParameter('uploads_directory');
        $filename = $request->get('filename');
        $file_full = $uploads_directory . '/' . $filename;
      
        $column = $request->get('text');
        // Array of columns from UI
        $list = array($column); 
        $fp = fopen('php://output', 'w');

        // Setting Latest Column Headers in new CSV
         foreach ($list as $fields) {
            fputcsv($fp, $fields);
      }

        $reader = Reader::createFromPath($file_full);
        $reader->setHeaderOffset(0);

        // Putting contents from second line of uploaded CSV
        $records = $reader->getRecords($column);
        foreach ($records as $offset => $record) {
            fputcsv($fp,$record);    
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv');
        
        // It's gonna output in a testing.csv file
        $response->headers->set('Content-Disposition', 'attachment; filename="testing.csv"');
        return $response;
    }
}