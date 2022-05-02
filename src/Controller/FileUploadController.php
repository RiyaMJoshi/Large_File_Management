<?php

namespace App\Controller;

use App\Entity\MetaTable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;

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
        //get and upload csv
    $file = $request->files->get('formFile');
    $uploads_directory = $this->getParameter('uploads_directory');
    $filename = md5(uniqid()) . '.' . $file->guessExtension();
    $file->move(
        $uploads_directory,
        $filename
    );
 
    $file_full = $uploads_directory . '/' . $filename;
    // Open and extract csv
    $filesize = filesize($file_full); // bytes
    $filesize = round($filesize / 1024,2);
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
