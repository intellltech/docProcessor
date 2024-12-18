<?php

defined('BASEPATH') OR exit('No direct script access allowed');


use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\Text;

require_once APPPATH . '../vendor/autoload.php';

class Welcome extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // Load necessary helpers and libraries
        $this->load->helper(['form', 'url']);
        $this->load->library('upload');
    }

    public function index()
    {
        $this->load->view('welcome_message');
    }

    public function process_doc()
    {
        // Configuration for template file
        $configTemplate = [
            'upload_path'   => './uploads/',
            'allowed_types' => '*',
            'max_size'      => 2048,
            'encrypt_name'  => TRUE
        ];
        $this->upload->initialize($configTemplate);
        
        // Upload template file
        if (!$this->upload->do_upload('templateFile')) {
            $response['error'] = 'Template upload error: ' . $this->upload->display_errors();
            print_r($response['error']);
            $this->load->view('welcome_message', ['error' => $response['error']]);
            return;
        }
        $templateData = $this->upload->data();
        $templateFilePath = './uploads/' . $templateData['file_name'];
        
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
            $this->load->view('welcome_message', ['error' => $response['error']]);
            return;
        }
        
        $jsonData = $this->upload->data();
        $jsonFilePath = './uploads/' . $jsonData['file_name'];
        
        // Read JSON data
        $data = json_decode(file_get_contents($jsonFilePath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response['error'] = 'Invalid JSON format.';
            $this->load->view('welcome_message', ['error' => $response['error']]);
            return;
        }
        // Process the document
        $processedFilePath = $this->process_document($templateFilePath, $data);

        // After processing, delete the original files (template and JSON)
        unlink($templateFilePath);
        unlink($jsonFilePath);

        // Success response with the processed file
        $response = [
            'success'       => true,
            'processedFile' => $processedFilePath
        ];
        // Output response as JSON for debugging
        echo json_encode($response);
    }

    private function process_document($templateFilePath, $data)
    {
        $extension = pathinfo($templateFilePath, PATHINFO_EXTENSION);
        $processedFilePath = './uploads/' . "result.docx";

        // Process based on file type
        switch ($extension) {
            case 'doc':
                $processedFilePath .= '.docx'; // Convert to .docx after processing
                $this->processWordDocument($templateFilePath, $data, $processedFilePath);
                break;
            case 'docx':
                $processedFilePath .= '.docx'; // Convert to .docx after processing
                $this->processWordDocument($templateFilePath, $data, $processedFilePath);
                break;
            case 'rtf':
                $processedFilePath .= '.docx'; // Convert to .docx after processing
                $this->processWordDocument($templateFilePath, $data, $processedFilePath);
                break;
            case 'odt':
                $processedFilePath .= '.docx'; // Convert to .docx after processing
                $this->processWordDocument($templateFilePath, $data, $processedFilePath);
                break;
            case 'xls':
            case 'xlsx':
                $processedFilePath .= '.xlsx';
                $this->process_excel_document($templateFilePath, $data, $processedFilePath);
                break;
            case 'pdf':
                $processedFilePath .= '.pdf';
                $this->process_pdf_document($templateFilePath, $data, $processedFilePath);
                break;
            default:
                throw new Exception('Unsupported file format.');
        }

        return $processedFilePath;
    }
    
    

    /**
     * Main method to process the word document with JSON data
     * 
     * @param string $dataPath Path to the DOCX template.
     * @param array $jsonData The JSON data to populate the template.
     * @param string $outputPath Path where the processed file will be saved.
     * @return bool Success or failure of the process.
     */
    public function processWordDocument($dataPath, $jsonData, $outputPath)
    {
        try {
            $templateProcessor = new TemplateProcessor($dataPath);
            
            // $templateProcessor->setMacroChars('{','}');
            // Flatten simple key-value pairs
            $simpleData = $this->flattenTopLevel($jsonData);
            $templateProcessor->setValues($simpleData);

            // Process array blocks (both indexed and associative arrays)
            $this->processArrayBlocks($jsonData, $templateProcessor);
            // $templateProcessor->cloneBlock('fpl_room_amenities_list',count($jsonData['fpl_hotel_amenities_list']),true,true,$jsonData['fpl_hotel_amenities_list']);
            // var_dump($jsonData['fpl_hotel_amenities_list']);
            // Save the processed document
            $templateProcessor->saveAs($outputPath);
            return true;
        } catch (\Exception $e) {
            error_log("Error in process_word_document: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Flatten the top-level of the JSON data, extracting non-array values.
     * 
     * @param array $data The JSON data.
     * @return array Flattened key-value pairs.
     */
    private function flattenTopLevel($data)
    {
        $flatData = [];
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $flatData[$key] = $value;
            }
        }
        return $flatData;
    }

    /**
     * Process array blocks from JSON data and apply them to the template.
     * This method handles both associative and indexed arrays for blocks.
     *
     * @param array $data The full JSON data.
     * @param TemplateProcessor $templateProcessor The template processor instance.
     */
    private function processArrayBlocks(array $data, TemplateProcessor $templateProcessor)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if ($this->isAssociative($value)) {
                    // For associative arrays, pass them directly
                    $this->processBlock($key, $value, $templateProcessor);
                } else {
                    // For indexed arrays, convert to object structure
                    $this->processBlock($key, $this->convertIndexedArrayToObject($value), $templateProcessor);
                }
            }
        }
    }

    /**
     * Convert an indexed array into an object structure (for blocks).
     * 
     * @param array $array The indexed array.
     * @return array The array converted to an object structure.
     */
    private function convertIndexedArrayToObject(array $array)
    {
        return array_map(function ($item) {
            return (object) $item; // Convert each item into an object
        }, $array);
    }

    /**
     * Check if the array is associative (non-numeric keys).
     * 
     * @param array $array The array to check.
     * @return bool True if the array is associative, false if it's indexed.
     */
    private function isAssociative(array $array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Process a block by cloning it and filling it with the data from the JSON.
     * This is used for both indexed and associative array-based blocks.
     * 
     * @param string $blockName The name of the block to process.
     * @param array $blockData The data to populate the block.
     * @param TemplateProcessor $templateProcessor The template processor instance.
     */
    private function processBlock(string $blockName, array $blockData, TemplateProcessor $templateProcessor)
    {
        // Clone the block and apply the data
        $templateProcessor->cloneBlock($blockName, count($blockData) - 1, true, false, $blockData);
    }



    
    
    private function process_excel_document($templateFilePath, $data, $processedFilePath)
    {
        // // Use PhpSpreadsheet to process Excel files
        // $this->load->library('PhpSpreadsheet');
        // $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($templateFilePath);

        // // Loop through all sheets and replace placeholders
        // foreach ($spreadsheet->getAllSheets() as $sheet) {
        //     foreach ($sheet->getCellCollection() as $cell) {
        //         $value = $sheet->getCell($cell)->getValue();
        //         if (is_string($value)) {
        //             $value = $this->replace_placeholders($value, $data);
        //             $sheet->getCell($cell)->setValue($value);
        //         }
        //     }
        // }

        // // Save the processed Excel file
        // $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        // $writer->save($processedFilePath);
    }

    private function process_pdf_document($templateFilePath, $data, $processedFilePath)
    {
        // // Use TCPDF to process PDF files (basic implementation)
        // $this->load->library('tcpdf');
        // $pdf = new TCPDF();

        // // Read the existing PDF
        // $pdf->setSourceFile($templateFilePath);
        // $tplIdx = $pdf->importPage(1);
        // $pdf->addPage();
        // $pdf->useTemplate($tplIdx);

        // // Replace placeholders (this example assumes simple text replacement)
        // $text = file_get_contents($templateFilePath);
        // $text = $this->replace_placeholders($text, $data);

        // // Add replaced content to PDF (for this example, we assume it’s basic text replacement)
        // $pdf->SetFont('helvetica', '', 12);
        // $pdf->Text(10, 10, $text);

        // // Save the processed PDF
        // $pdf->Output($processedFilePath, 'F');
    }

    
}
