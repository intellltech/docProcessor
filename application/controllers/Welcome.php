<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        
    }

    public function index()
    {
        $this->load->view('welcome_message');
    }

    public function upload_1()
{
    $this->load->helper(['form', 'url']);
    $this->load->library('upload');

    // Configuration for template file
    $configTemplate = [
        'upload_path'   => './uploads/',
        'allowed_types' => 'doc|docx|rtf|odt|xls|xlsx|pdf',
        'max_size'      => 2048,
        'encrypt_name'  => TRUE
    ];
    $this->upload->initialize($configTemplate);

    // Upload template file
    if (!$this->upload->do_upload('templateFile')) {
        $response['error'] = 'Template upload error: ' . $this->upload->display_errors();
        $this->load->view('welcome_message', ['error' => $response['error']]);
        return;
    }
    $templateData = $this->upload->data();

    // Configuration for JSON file
    $configJSON = [
        'upload_path'   => './uploads/',
        'allowed_types' => '*',
        'max_size'      => 1024,
        'encrypt_name'  => TRUE
    ];
    $this->upload->initialize($configJSON);

    // Upload JSON file
    if (!$this->upload->do_upload('dataFile')) {
        $response['error'] = 'JSON upload error: ' . $this->upload->display_errors();
        log_message('error', print_r($_FILES, true)); // Debugging log
        $this->load->view('welcome_message', ['error' => $response['error']]);
        return;
    }

    $jsonData = $this->upload->data();

    // Success response
    $response = [
        'success'      => true,
        'templateFile' => $templateData,
        'dataFile'     => $jsonData
    ];

    echo json_encode($response); // For debugging
}



}
