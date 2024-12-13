<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Processor</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
        // Ensure file selection validation before submission
        function validateForm(event) {
            const templateFile = document.getElementById('templateFile').files[0];
            const dataFile = document.getElementById('dataFile').files[0];

            if (!templateFile) {
                alert('Please select a document template file.');
                event.preventDefault();
                return false;
            }

            if (!dataFile) {
                alert('Please select a JSON data file.');
                event.preventDefault();
                return false;
            }

            return true;
        }
    </script>
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header">
                <h3>Document Processor</h3>
            </div>
            <div class="card-body">
                <form id="uploadForm" action="welcome/upload_1" method="post" enctype="multipart/form-data" onsubmit="return validateForm(event);">
                    <!-- File Input for Template -->
                    <div class="form-group">
                        <label for="templateFile">Document Template</label>
                        <input type="file" id="templateFile" name="templateFile" class="form-control" accept=".doc,.docx,.rtf,.odt,.xls,.xlsx,.pdf" required>
                        <small class="form-text text-muted">Supported formats: .doc, .docx, .rtf, .odt, .xls, .xlsx, .pdf</small>
                    </div>

                    <!-- File Input for JSON -->
                    <div class="form-group">
                        <label for="dataFile">Data JSON File</label>
                        <input type="file" id="dataFile" name="dataFile" class="form-control" accept=".json" required>
                        <small class="form-text text-muted">Upload the JSON file containing data for placeholder replacement.</small>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary btn-block" id="processButton">Process Document</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>