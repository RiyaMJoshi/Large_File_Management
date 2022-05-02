<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ModifyFileController extends AbstractController
{
    #[Route('/modify', name: 'modify_file')]
    public function modify_file()//: Response
    {
        $uploads_directory = $this->getParameter('uploads_directory');
        $files = $uploads_directory . '/files/';

        $file = $files . "demo.csv";
        $fileSize = filesize($file);
        var_dump($fileSize);
        //dd($file);

        // Open the file
        if (($handle = fopen($file, "r")) !== false) {
            $columns = fgetcsv($handle, 1000, ",");
            var_dump($columns);
            die();
            fclose($handle);
        }

        return $this->render('file_upload/index.html.twig', [
            'controller_name' => 'FileUploadController',
        ]);
    }
}
