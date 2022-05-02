<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
function upload(Request $request)
    {
    $file = $request->files->get('formFile');
    $uploads_directory = $this->getParameter('uploads_directory');
    $filename = md5(uniqid()) . '.' . $file->guessExtension();
    $file->move(
        $uploads_directory,
        $filename
    );
 
    $file_full = $uploads_directory . '/' . $filename;
    // Open the file
    $filesize = filesize($file_full); // bytes
    $filesize = round($filesize / 1024,2);
    dd($filesize);
    if (($handle = fopen($file_full, "r")) !== false) {
        $columns = fgetcsv($handle, 1000, ",");
        var_dump($columns);
        die();
        fclose($handle);
    }
    return new Response("file upload success");
}
}
