<?php

namespace App\Controller;

use App\Entity\MetaTable;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class FileUploadController extends AbstractController
{
    #[Route('/', name:'app_homepage')]
    function index(): Response
    {
        return $this->render('file_upload/index.html.twig', [
            'controller_name' => 'FileUploadController',
        ]);
    }

    #[Route('/uploadfile', name:'app_upload_file')]
    function upload(Request $request, ManagerRegistry $doctrine)
    {
        //Get and Upload CSV
        $file = $request->files->get('formFile');
        $uploads_directory = $this->getParameter('uploads_directory');

        // Extract file if it is zip
        if ($file->guessExtension() == 'zip') {
            $zipArchive = new ZipArchive();
            $zipArchive->open($file);

            //dd($file);
            if ($zipArchive) {
                // Get only the first available CSV File from the Zip
                $stat = $zipArchive->statIndex(0);
                //$file = basename($stat['name']);
                var_dump(basename($stat['name']));
            }
        }
        die();

        $filename = md5(uniqid()) . '.' . $file->guessExtension();
        //$filename = md5(uniqid()) . '.' . 'csv';
        $file->move(
            $uploads_directory,
            $filename
        );

        $file_full = $uploads_directory . '/' . $filename;
        // Open and extract csv
        $filesize = filesize($file_full); // bytes
        $filesize = round($filesize / 1024, 2);
        if (($handle = fopen($file_full, "r")) !== false) {
            $columns = fgetcsv($handle, 1000, ",");
            fclose($handle);
        }

        //save to meta table in db

        $metaTable = new MetaTable();
        $em = $doctrine->getManager();
        $metaTable->setFilename($filename);
        $metaTable->setFilesize($filesize);
        $metaTable->setColumns($columns);
        $em->persist($metaTable);
        $em->flush();

        return new Response("file upload success");
    }
}
