<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\FileUploader;

class FileUploadController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        return $this->render('file_upload/index.html.twig', [
            'controller_name' => 'FileUploadController',
        ]);
    }
    #[Route('/uploadfile', name: 'app_upload_file')]
    public function upload(Request $request)
    {
        //dd($request->files->get('file'));

        // dd($request);
        // die;
        $file=$request->files->get('formFile');
        $uploads_directory=$this->getParameter('uploads_directory');

        $extension = $file->guessExtension();
       // dd($extension);
        // if ($extension === 'csv') {
        //     # code...
        // }
        $filename=md5(uniqid()).'.'.$file->guessExtension();
        $file->move(
            $uploads_directory,
            $filename
        );
        return new Response("file upload success");
    }
}
