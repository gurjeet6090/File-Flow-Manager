<?php
// Define upload directory
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Get files in current directory
function getFiles($path = '') {
    $files = [];
    $fullPath = UPLOAD_DIR . $path;
    
    // Add parent directory link if not in root
    if ($path !== '') {
        $parentPath = dirname($path);
        if ($parentPath === '.') $parentPath = '';
        $files[] = [
            'name' => '..',
            'type' => 'folder',
            'size' => '0 B',
            'modified' => '',
            'full_path' => $parentPath,
            'icon' => 'fas fa-folder-open',
            'icon_class' => 'folder'
        ];
    }
    
    if (is_dir($fullPath)) {
        $items = scandir($fullPath);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = ($path ? $path . '/' : '') . $item;
            $fullItemPath = $fullPath . '/' . $item;
            
            if (is_dir($fullItemPath)) {
                $files[] = [
                    'name' => $item,
                    'type' => 'folder',
                    'size' => '0 B',
                    'modified' => date('M j, Y', filemtime($fullItemPath)),
                    'full_path' => $itemPath,
                    'icon' => 'fas fa-folder',
                    'icon_class' => 'folder'
                ];
            } else {
                $extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
                $fileSize = filesize($fullItemPath);
                $fileInfo = [
                    'name' => $item,
                    'type' => 'file',
                    'size' => formatFileSize($fileSize),
                    'modified' => date('M j, Y', filemtime($fullItemPath)),
                    'full_path' => $itemPath,
                    'icon' => getFileIcon($extension),
                    'icon_class' => getFileIconClass($extension)
                ];
                $files[] = $fileInfo;
            }
        }
    }
    
    return $files;
}

// Upload file
function uploadFile() {
    if (!isset($_FILES['file']) || empty($_FILES['file']['name'][0])) {
        return "Error: No files selected.";
    }
    
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    $targetDir = UPLOAD_DIR . $currentPath;
    
    // Create directory if it doesn't exist
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $uploadedFiles = 0;
    $totalFiles = count($_FILES['file']['name']);
    $errorMessages = [];
    
    for ($i = 0; $i < $totalFiles; $i++) {
        if ($_FILES['file']['error'][$i] !== UPLOAD_ERR_OK) {
            $errorMessages[] = "Error uploading file '{$_FILES['file']['name'][$i]}': " . getUploadError($_FILES['file']['error'][$i]);
            continue;
        }
        
        // Check file size
        if ($_FILES['file']['size'][$i] > MAX_FILE_SIZE) {
            $errorMessages[] = "Error: File '{$_FILES['file']['name'][$i]}' exceeds maximum size of 50MB.";
            continue;
        }
        
        $fileName = basename($_FILES['file']['name'][$i]);
        $targetFile = $targetDir . '/' . $fileName;
        
        // Check if file already exists
        if (file_exists($targetFile)) {
            $errorMessages[] = "Error: File '$fileName' already exists.";
            continue;
        }
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['file']['tmp_name'][$i], $targetFile)) {
            $uploadedFiles++;
        } else {
            $errorMessages[] = "Error: Could not upload file '$fileName'.";
        }
    }
    
    if ($uploadedFiles > 0) {
        $message = "Successfully uploaded $uploadedFiles file(s).";
        if (!empty($errorMessages)) {
            $message .= " Errors: " . implode(', ', $errorMessages);
        }
        return $message;
    } else {
        return "Error: No files were uploaded. " . implode(', ', $errorMessages);
    }
}

// Get upload error message
function getUploadError($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return "File too large (server limit).";
        case UPLOAD_ERR_FORM_SIZE:
            return "File too large (form limit).";
        case UPLOAD_ERR_PARTIAL:
            return "File upload was incomplete.";
        case UPLOAD_ERR_NO_FILE:
            return "No file was uploaded.";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Missing temporary folder.";
        case UPLOAD_ERR_CANT_WRITE:
            return "Failed to write to disk.";
        case UPLOAD_ERR_EXTENSION:
            return "File upload stopped by extension.";
        default:
            return "Unknown upload error.";
    }
}

// Delete file or folder
function deleteFile($filePath) {
    $fullPath = UPLOAD_DIR . $filePath;
    
    if (!file_exists($fullPath)) {
        return "Error: File or folder not found.";
    }
    
    if (is_dir($fullPath)) {
        // Delete folder recursively
        if (deleteFolderRecursive($fullPath)) {
            return "Folder deleted successfully.";
        } else {
            return "Error: Could not delete folder.";
        }
    } else {
        // Delete file
        if (unlink($fullPath)) {
            return "File deleted successfully.";
        } else {
            return "Error: Could not delete file.";
        }
    }
}

// Recursively delete folder
function deleteFolderRecursive($dir) {
    if (!is_dir($dir)) return false;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            deleteFolderRecursive($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

// Rename file or folder
function renameFile($oldName, $newName) {
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    $oldPath = UPLOAD_DIR . $currentPath . '/' . $oldName;
    $newPath = UPLOAD_DIR . $currentPath . '/' . $newName;
    
    if (!file_exists($oldPath)) {
        return "Error: File or folder not found.";
    }
    
    if (file_exists($newPath)) {
        return "Error: A file or folder with name '$newName' already exists.";
    }
    
    if (rename($oldPath, $newPath)) {
        return "Successfully renamed to '$newName'.";
    } else {
        return "Error: Could not rename file or folder.";
    }
}

// Create new folder
function createFolder($folderName) {
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    $folderPath = UPLOAD_DIR . $currentPath . '/' . $folderName;
    
    // Validate folder name
    if (empty($folderName)) {
        return "Error: Folder name cannot be empty.";
    }
    
    // Check for invalid characters
    if (preg_match('/[<>:"|?*]/', $folderName)) {
        return "Error: Folder name contains invalid characters.";
    }
    
    if (file_exists($folderPath)) {
        return "Error: Folder '$folderName' already exists.";
    }
    
    if (mkdir($folderPath, 0777, true)) {
        return "Folder '$folderName' created successfully.";
    } else {
        return "Error: Could not create folder.";
    }
}

// Move file or folder
function moveFile($fileToMove, $targetFolder) {
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    $sourcePath = UPLOAD_DIR . $currentPath . '/' . $fileToMove;
    $targetPath = UPLOAD_DIR . $targetFolder . '/' . $fileToMove;
    
    if (!file_exists($sourcePath)) {
        return "Error: File or folder not found.";
    }
    
    if (file_exists($targetPath)) {
        return "Error: A file or folder with that name already exists in the target location.";
    }
    
    // Create target directory if it doesn't exist
    $targetDir = UPLOAD_DIR . $targetFolder;
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (rename($sourcePath, $targetPath)) {
        return "Successfully moved to '$targetFolder'.";
    } else {
        return "Error: Could not move file or folder.";
    }
}

// Copy file or folder
function copyFile($fileToCopy, $targetFolder) {
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    $sourcePath = UPLOAD_DIR . $currentPath . '/' . $fileToCopy;
    $targetPath = UPLOAD_DIR . $targetFolder . '/' . $fileToCopy;
    
    if (!file_exists($sourcePath)) {
        return "Error: File or folder not found.";
    }
    
    if (file_exists($targetPath)) {
        return "Error: A file or folder with that name already exists in the target location.";
    }
    
    // Create target directory if it doesn't exist
    $targetDir = UPLOAD_DIR . $targetFolder;
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (is_dir($sourcePath)) {
        // Copy folder recursively
        if (copyFolderRecursive($sourcePath, $targetPath)) {
            return "Successfully copied to '$targetFolder'.";
        } else {
            return "Error: Could not copy folder.";
        }
    } else {
        // Copy file
        if (copy($sourcePath, $targetPath)) {
            return "Successfully copied to '$targetFolder'.";
        } else {
            return "Error: Could not copy file.";
        }
    }
}

// Recursively copy folder
function copyFolderRecursive($source, $dest) {
    if (!is_dir($dest)) {
        mkdir($dest, 0777, true);
    }
    
    $items = scandir($source);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $srcPath = $source . '/' . $item;
        $dstPath = $dest . '/' . $item;
        
        if (is_dir($srcPath)) {
            copyFolderRecursive($srcPath, $dstPath);
        } else {
            copy($srcPath, $dstPath);
        }
    }
    
    return true;
}

// Format file size
function formatFileSize($bytes) {
    if ($bytes === 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
}

// Get file icon
function getFileIcon($extension) {
    $icons = [
        'pdf' => 'fas fa-file-pdf',
        'jpg' => 'fas fa-file-image',
        'jpeg' => 'fas fa-file-image',
        'png' => 'fas fa-file-image',
        'gif' => 'fas fa-file-image',
        'bmp' => 'fas fa-file-image',
        'svg' => 'fas fa-file-image',
        'doc' => 'fas fa-file-word',
        'docx' => 'fas fa-file-word',
        'xls' => 'fas fa-file-excel',
        'xlsx' => 'fas fa-file-excel',
        'ppt' => 'fas fa-file-powerpoint',
        'pptx' => 'fas fa-file-powerpoint',
        'zip' => 'fas fa-file-archive',
        'rar' => 'fas fa-file-archive',
        '7z' => 'fas fa-file-archive',
        'tar' => 'fas fa-file-archive',
        'gz' => 'fas fa-file-archive',
        'mp3' => 'fas fa-file-audio',
        'wav' => 'fas fa-file-audio',
        'ogg' => 'fas fa-file-audio',
        'mp4' => 'fas fa-file-video',
        'avi' => 'fas fa-file-video',
        'mov' => 'fas fa-file-video',
        'mkv' => 'fas fa-file-video',
        'txt' => 'fas fa-file-text',
        'rtf' => 'fas fa-file-text',
        'js' => 'fas fa-file-code',
        'php' => 'fas fa-file-code',
        'html' => 'fas fa-file-code',
        'css' => 'fas fa-file-code',
        'json' => 'fas fa-file-code',
        'xml' => 'fas fa-file-code',
        'sql' => 'fas fa-file-code',
        'py' => 'fas fa-file-code',
        'java' => 'fas fa-file-code',
        'cpp' => 'fas fa-file-code'
    ];
    
    return $icons[$extension] ?? 'fas fa-file';
}

// Get file icon class
function getFileIconClass($extension) {
    $classes = [
        'pdf' => 'document',
        'jpg' => 'image',
        'jpeg' => 'image',
        'png' => 'image',
        'gif' => 'image',
        'bmp' => 'image',
        'svg' => 'image',
        'doc' => 'document',
        'docx' => 'document',
        'xls' => 'spreadsheet',
        'xlsx' => 'spreadsheet',
        'ppt' => 'document',
        'pptx' => 'document',
        'zip' => 'archive',
        'rar' => 'archive',
        '7z' => 'archive',
        'tar' => 'archive',
        'gz' => 'archive',
        'mp3' => 'audio',
        'wav' => 'audio',
        'ogg' => 'audio',
        'mp4' => 'video',
        'avi' => 'video',
        'mov' => 'video',
        'mkv' => 'video',
        'txt' => 'document',
        'rtf' => 'document',
        'js' => 'code',
        'php' => 'code',
        'html' => 'code',
        'css' => 'code',
        'json' => 'code',
        'xml' => 'code',
        'sql' => 'code',
        'py' => 'code',
        'java' => 'code',
        'cpp' => 'code'
    ];
    
    return $classes[$extension] ?? 'other';
}

// Get folder tree for sidebar
function getFolderTree($dir = '', $level = 0) {
    $fullPath = UPLOAD_DIR . $dir;
    $html = '';
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    
    if (is_dir($fullPath)) {
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = ($dir ? $dir . '/' : '') . $item;
            $fullItemPath = $fullPath . '/' . $item;
            
            if (is_dir($fullItemPath)) {
                $isActive = $currentPath === $itemPath ? 'active' : '';
                $padding = $level * 20;
                
                $html .= '<a href="index.php?path=' . urlencode($itemPath) . '" class="nav-link ' . $isActive . '" style="padding-left: ' . ($padding + 15) . 'px">';
                $html .= '<i class="fas fa-folder"></i>';
                $html .= '<span>' . htmlspecialchars($item) . '</span>';
                $html .= '</a>';
                
                // Recursively add subfolders
                $html .= getFolderTree($itemPath, $level + 1);
            }
        }
    }
    
    return $html;
}

// Get folder options for move/copy modals
function getFolderOptions($dir = '', $level = 0) {
    $fullPath = UPLOAD_DIR . $dir;
    $html = '';
    $currentPath = isset($_GET['path']) ? $_GET['path'] : '';
    
    if (is_dir($fullPath)) {
        $items = scandir($fullPath);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $itemPath = ($dir ? $dir . '/' : '') . $item;
            $fullItemPath = $fullPath . '/' . $item;
            
            if (is_dir($fullItemPath) && $itemPath !== $currentPath) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $level);
                $html .= '<option value="' . $itemPath . '">' . $indent . htmlspecialchars($item) . '</option>';
                $html .= getFolderOptions($itemPath, $level + 1);
            }
        }
    }
    
    return $html;
}

// Get storage information - FIXED VERSION
function getStorageInfo() {
    $storageData = calculateStorage(UPLOAD_DIR);
    
    // For demo purposes, assume 10GB total storage
    $totalStorage = 10 * 1024 * 1024 * 1024; // 10GB in bytes
    $usedStorage = $storageData['size'];
    $availableStorage = $totalStorage - $usedStorage;
    $usagePercentage = $totalStorage > 0 ? round(($usedStorage / $totalStorage) * 100, 1) : 0;
    
    return [
        'used' => formatFileSize($usedStorage),
        'available' => formatFileSize($availableStorage),
        'percentage' => $usagePercentage,
        'file_count' => $storageData['file_count'],
        'folder_count' => $storageData['folder_count'],
        'total_items' => $storageData['file_count'] + $storageData['folder_count']
    ];
}

// Fixed recursive storage calculation
function calculateStorage($dir) {
    $totalSize = 0;
    $fileCount = 0;
    $folderCount = 0;
    
    if (is_dir($dir)) {
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $folderCount++;
                $subStorage = calculateStorage($path);
                $totalSize += $subStorage['size'];
                $fileCount += $subStorage['file_count'];
                $folderCount += $subStorage['folder_count'];
            } else {
                $fileCount++;
                $totalSize += filesize($path);
            }
        }
    }
    
    return [
        'size' => $totalSize,
        'file_count' => $fileCount,
        'folder_count' => $folderCount
    ];
}

// Debug function to check if functions are working
function debugFileSystem() {
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<h4>Debug Information:</h4>";
    
    // Check if uploads directory exists and is writable
    echo "UPLOAD_DIR exists: " . (file_exists(UPLOAD_DIR) ? 'Yes' : 'No') . "<br>";
    echo "UPLOAD_DIR is writable: " . (is_writable(UPLOAD_DIR) ? 'Yes' : 'No') . "<br>";
    
    // Test getFiles function
    $testFiles = getFiles();
    echo "Files found: " . count($testFiles) . "<br>";
    
    // Test storage info
    $storageInfo = getStorageInfo();
    echo "Storage calculation working: " . ($storageInfo['file_count'] >= 0 ? 'Yes' : 'No') . "<br>";
    
    echo "</div>";
}

// Uncomment the line below to see debug information
// debugFileSystem();
?>