<?php
// Include session management
require_once 'session_manager.php';

// Require authentication
requireAuth();

// Include file operations
require_once 'file_functions.php';

// Handle file operations
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload'])) {
        $message = uploadFile();
    } elseif (isset($_POST['delete'])) {
        $message = deleteFile($_POST['file_path']);
    } elseif (isset($_POST['rename'])) {
        $message = renameFile($_POST['old_name'], $_POST['new_name']);
    } elseif (isset($_POST['create_folder'])) {
        $message = createFolder($_POST['folder_name']);
    } elseif (isset($_POST['move_file'])) {
        $message = moveFile($_POST['file_to_move'], $_POST['target_folder']);
    } elseif (isset($_POST['copy_file'])) {
        $message = copyFile($_POST['file_to_copy'], $_POST['target_folder']);
    }
}

// Get current directory
$currentPath = isset($_GET['path']) ? $_GET['path'] : '';
$files = getFiles($currentPath);
$folderTree = getFolderTree();
$storageInfo = getStorageInfo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FileFlow - Modern PHP File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #64748b;
            --dark: #0f172a;
            --darker: #020617;
            --light: #f8fafc;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --card-bg: #1e293b;
            --sidebar-bg: #0f172a;
            --hover-bg: #334155;
            --border-color: #334155;
            --glass-bg: rgba(30, 41, 59, 0.8);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, var(--darker), var(--dark));
            color: var(--light);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        /* Sidebar Styles */
        .sidebar {
            background: var(--sidebar-bg);
            min-height: 100vh;
            border-right: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: fixed;
            width: 280px;
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar.collapsed {
            transform: translateX(-100%);
        }

        .logo {
            padding: 1.5rem 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo h2 {
            font-weight: 700;
            background: linear-gradient(90deg, var(--primary), var(--info));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--light);
            padding: 0.75rem 1rem;
            margin: 0.25rem 0.5rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
            cursor: pointer;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--hover-bg);
            color: white;
        }

        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 0;
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            background: var(--sidebar-bg);
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .breadcrumb {
            background: transparent;
            margin: 0;
            flex-wrap: nowrap;
            overflow: hidden;
        }

        .breadcrumb-item {
            white-space: nowrap;
        }

        .breadcrumb-item a {
            color: var(--primary);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--light);
        }

        .search-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 300px;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            background: transparent;
            border: none;
            color: var(--light);
            width: 100%;
            outline: none;
        }

        .search-box input::placeholder {
            color: var(--secondary);
        }

        .user-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
            color: white;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success), #059669);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .btn-success-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
            color: white;
        }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-info h3 {
            font-size: 1.5rem;
            margin: 0;
        }

        .stat-info p {
            color: var(--secondary);
            margin: 0;
        }

        /* Files Section */
        .files-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .view-options {
            display: flex;
            gap: 0.5rem;
        }

        .view-btn {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--light);
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .view-btn.active {
            background: var(--primary);
            color: white;
        }

        /* Files Grid/List */
        .files-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
        }

        .files-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .files-list .file-card {
            display: flex;
            gap:10px;
            flex-direction:row;
            align-items: center;
            text-align: left;
            padding: 1rem;
            min-height: auto;
        }

        .files-list .file-icon {
            margin-bottom: 0;
            margin-right: 1rem;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .files-list .file-name {
            flex: 1;
            margin-bottom: 0;
            min-width: 0;
        }

        .files-list .file-meta {
            margin-left: auto;
            margin-right: 1rem;
            white-space: nowrap;
        }

        .files-list .file-actions {
            position: static;
            opacity: 1;
            margin-left: auto;
        }

        /* File Cards */
        .file-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            border-color: var(--primary);
        }

        .file-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
            flex-shrink: 0;
        }

        .file-icon.folder {
            color: var(--warning);
        }

        .file-icon.image {
            color: var(--success);
        }

        .file-icon.document {
            color: var(--info);
        }

        .file-icon.spreadsheet {
            color: var(--success);
        }

        .file-icon.code {
            color: var(--primary);
        }

        .file-icon.audio {
            color: var(--info);
        }

        .file-icon.video {
            color: var(--danger);
        }

        .file-icon.archive {
            color: var(--warning);
        }

        .file-icon.other {
            color: var(--secondary);
        }

        .file-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex-shrink: 0;
        }

        .file-meta {
            color: var(--secondary);
            font-size: 0.875rem;
            flex-shrink: 0;
        }

        .file-actions {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            gap: 0.5rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .file-card:hover .file-actions {
            opacity: 1;
        }

        .file-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--border-color);
            color: var(--light);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-action-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Progress Bar */
        .progress-bar {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 0.5rem;
        }

        .progress {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--info));
            border-radius: 3px;
            transition: width 0.5s ease;
        }

        /* Context Menu */
        .context-menu {
            position: fixed;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            z-index: 1100;
            display: none;
            min-width: 180px;
            backdrop-filter: blur(10px);
        }

        .context-menu-item {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
            color: var(--light);
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .context-menu-item:hover {
            background: var(--hover-bg);
        }

        /* Modal Customizations */
        .modal-content {
            background: var(--card-bg);
            color: var(--light);
            border: 1px solid var(--border-color);
        }

        .modal-header {
            border-bottom: 1px solid var(--border-color);
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
        }

        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Dropdown Customizations */
        .dropdown-menu {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        .dropdown-item {
            color: var(--light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dropdown-item:hover {
            background: var(--hover-bg);
            color: var(--light);
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .sidebar {
                width: 250px;
            }
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .search-box {
                min-width: auto;
                flex: 1;
            }
        }

        @media (max-width: 768px) {
            .files-list .file-card{
                gap: 10px;
               flex-direction: row;
            }
            .main-content {
                padding: 1rem;
            }
            .stats-cards {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 1rem;
            }
            .stat-card {
                padding: 1rem;
            }
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            .files-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 1rem;
            }
            .file-card {
                padding: 1rem;
                min-height: 150px;
            }
            .file-icon {
                font-size: 2rem;
            }
            .header {
                flex-direction: column;
                align-items: stretch;
            }
            .user-actions {
                justify-content: space-between;
            }
            .search-box {
                max-width: none;
                order: -1;
            }
        }

        @media (max-width: 576px) {
            .files-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            .file-card {
                min-height: 130px;
                padding: 0.75rem;
            }
            .file-icon {
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
            }
            .file-name {
                font-size: 0.9rem;
            }
            .file-meta {
                font-size: 0.8rem;
            }
            .btn-primary-custom, .btn-success-custom {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Selection State */
        .file-card.selected {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.1);
        }

        /* File Preview */
        .file-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <!-- Mobile Header -->
    <div class="mobile-header">
        <button class="btn btn-primary-custom" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo">
            <h2><i class="fas fa-folder-tree me-2"></i>FileFlow</h2>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <h2><i class="fas fa-folder-tree me-2"></i>FileFlow</h2>
            <button class="btn btn-sm btn-outline-light d-none" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="nav flex-column mt-4">
            <a href="index.php" class="nav-link <?php echo $currentPath === '' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#" class="nav-link" onclick="showFavorites()">
                <i class="fas fa-star"></i>
                <span>Favorites</span>
            </a>
            <a href="#" class="nav-link" onclick="showRecent()">
                <i class="fas fa-clock"></i>
                <span>Recent</span>
            </a>
            
            <div class="mt-4 px-3">
                <h6 class="text-uppercase text-muted small fw-bold">Quick Actions</h6>
            </div>
            
            <button class="btn btn-primary-custom m-2" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-cloud-upload-alt me-2"></i>Upload File
            </button>
            <button class="btn btn-success-custom m-2" data-bs-toggle="modal" data-bs-target="#folderModal">
                <i class="fas fa-folder-plus me-2"></i>New Folder
            </button>
            
            <div class="mt-4 px-3">
                <h6 class="text-uppercase text-muted small fw-bold">Folders</h6>
            </div>
            
            <!-- Dynamic Folder Tree -->
            <div class="folder-tree">
                <?php echo $folderTree; ?>
            </div>
            
            <div class="mt-4 px-3">
                <h6 class="text-uppercase text-muted small fw-bold">Storage</h6>
                <div class="mt-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small><?php echo $storageInfo['used']; ?> used</small>
                        <small><?php echo $storageInfo['percentage']; ?>%</small>
                    </div>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $storageInfo['percentage']; ?>%"></div>
                    </div>
                    <small class="text-muted"><?php echo $storageInfo['available']; ?> available</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" id="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <?php 
                    $pathParts = $currentPath ? explode('/', $currentPath) : [];
                    $accumulatedPath = '';
                    foreach ($pathParts as $index => $part): 
                        $accumulatedPath .= $accumulatedPath ? '/' . $part : $part;
                        $isLast = $index === count($pathParts) - 1;
                    ?>
                        <li class="breadcrumb-item <?php echo $isLast ? 'active' : ''; ?>">
                            <?php if (!$isLast): ?>
                                <a href="index.php?path=<?php echo urlencode($accumulatedPath); ?>">
                                    <?php echo htmlspecialchars($part); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($part); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>
            
            <div class="user-actions">
                <div class="search-box">
                    <i class="fas fa-search text-muted"></i>
                    <input type="text" placeholder="Search files and folders..." id="searchInput" oninput="filterFiles()">
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload
                </button>
                <button class="btn btn-outline-light" onclick="toggleView()" id="viewToggle">
                    <i class="fas fa-th" id="viewIcon"></i>
                </button>
                <a class="btn btn-outline-light" href="auth.php?action=logout">
                    <i class="fas fa-sign-out" id="viewIcon"></i>
                            </a>
            </div>
        </div>
        
        <!-- Message Alert -->
        <?php if ($message): ?>
        <div class="alert alert-dismissible fade show <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card fade-in">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.2); color: var(--primary);">
                    <i class="fas fa-file"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $storageInfo['file_count']; ?></h3>
                    <p>Total Files</p>
                </div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.2); color: var(--warning);">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $storageInfo['folder_count']; ?></h3>
                    <p>Folders</p>
                </div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.2); color: var(--success);">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $storageInfo['used']; ?></h3>
                    <p>Storage Used</p>
                </div>
            </div>
            <div class="stat-card fade-in">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.2); color: var(--danger);">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $storageInfo['total_items']; ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
        </div>
        
        <div class="glass-card p-4">
            <div class="files-header">
                <h4 class="mb-0"><?php echo $currentPath ? 'Folder: ' . basename($currentPath) : 'All Files'; ?></h4>
                <div class="view-options">
                    <button class="view-btn active" id="gridViewBtn" onclick="setGridView()">
                        <i class="fas fa-th"></i>
                    </button>
                    <button class="view-btn" id="listViewBtn" onclick="setListView()">
                        <i class="fas fa-list"></i>
                    </button>
                </div>
            </div>
            
            <div class="files-grid mt-4" id="filesContainer">
                <?php if (empty($files)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <h4>No files found</h4>
                        <p>Upload your first file or create a new folder</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($files as $file): ?>
                        <div class="file-card fade-in" data-name="<?php echo strtolower($file['name']); ?>" 
                             onclick="handleFileClick('<?php echo $file['type']; ?>', '<?php echo $file['full_path']; ?>')"
                             oncontextmenu="showContextMenu(event, '<?php echo $file['name']; ?>', '<?php echo $file['type']; ?>', '<?php echo $file['full_path']; ?>')">
                            <div class="file-actions">
                                <div class="dropdown">
                                    <button class="file-action-btn" onclick="event.stopPropagation(); showDropdownMenu(event, '<?php echo $file['name']; ?>', '<?php echo $file['type']; ?>', '<?php echo $file['full_path']; ?>')">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="file-icon <?php echo $file['type'] === 'folder' ? 'folder' : $file['icon_class']; ?>">
                                <i class="<?php echo $file['type'] === 'folder' ? 'fas fa-folder' : $file['icon']; ?>"></i>
                            </div>
                            <div class="file-name"><?php echo htmlspecialchars($file['name']); ?></div>
                            <div class="file-meta">
                                <?php if ($file['type'] === 'folder'): ?>
                                    Folder
                                <?php else: ?>
                                    <?php echo $file['size']; ?> • <?php echo $file['modified']; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Context Menu -->
    <div class="context-menu" id="contextMenu">
        <button class="context-menu-item" onclick="downloadSelectedFile()">
            <i class="fas fa-download"></i>
            <span>Download</span>
        </button>
        <button class="context-menu-item" onclick="previewSelectedFile()">
            <i class="fas fa-eye"></i>
            <span>Preview</span>
        </button>
        <button class="context-menu-item" onclick="copylinkSelectedFile()">
            <i class="fas fa-link"></i>
            <span>Copy Link</span>
        </button>
        <button class="context-menu-item" onclick="showRenameModal()">
            <i class="fas fa-edit"></i>
            <span>Rename</span>
        </button>
        <button class="context-menu-item" onclick="showMoveModal()">
            <i class="fas fa-arrows-alt"></i>
            <span>Move</span>
        </button>
        <button class="context-menu-item" onclick="showCopyModal()">
            <i class="fas fa-copy"></i>
            <span>Copy</span>
        </button>
        <button class="context-menu-item text-danger" onclick="deleteSelectedFile()">
            <i class="fas fa-trash"></i>
            <span>Delete</span>
        </button>
    </div>
    
    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Upload Files</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="text-center py-3">
                            <div class="mb-3">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary"></i>
                            </div>
                            <div class="mb-3">
                                <input type="file" class="form-control" name="file[]" multiple required>
                            </div>
                            <p class="text-muted">Max file size: 50MB per file</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload" class="btn btn-primary-custom">Upload Files</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Folder Modal -->
    <div class="modal fade" id="folderModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Create New Folder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="folder_name" class="form-label">Folder Name</label>
                            <input type="text" class="form-control" id="folder_name" name="folder_name" required placeholder="Enter folder name">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_folder" class="btn btn-primary-custom">Create Folder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Rename Modal -->
    <div class="modal fade" id="renameModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Rename</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="new_name" class="form-label">New Name</label>
                            <input type="text" class="form-control" id="new_name" name="new_name" required>
                            <input type="hidden" id="old_name" name="old_name">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="rename" class="btn btn-primary-custom">Rename</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Move Modal -->
    <div class="modal fade" id="moveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Move File/Folder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="target_folder" class="form-label">Select Target Folder</label>
                            <select class="form-control" id="target_folder" name="target_folder" required>
                                <option value="">Root Directory</option>
                                <?php echo getFolderOptions(); ?>
                            </select>
                            <input type="hidden" id="file_to_move" name="file_to_move">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="move_file" class="btn btn-primary-custom">Move</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Copy Modal -->
    <div class="modal fade" id="copyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Copy File/Folder</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="copy_target_folder" class="form-label">Select Target Folder</label>
                            <select class="form-control" id="copy_target_folder" name="target_folder" required>
                                <option value="">Root Directory</option>
                                <?php echo getFolderOptions(); ?>
                            </select>
                            <input type="hidden" id="file_to_copy" name="file_to_copy">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="copy_file" class="btn btn-primary-custom">Copy</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content glass-card border-0">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="previewTitle">File Preview</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="previewContent">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let selectedFile = {
            name: '',
            type: '',
            path: ''
        };
        let currentView = 'grid';

        // Initialize the file manager
        document.addEventListener('DOMContentLoaded', function() {
            // Close context menu when clicking elsewhere
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.context-menu') && !e.target.closest('.file-action-btn')) {
                    hideContextMenu();
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', handleResize);
        });

        // Navigation functions
        function handleFileClick(type, path) {
            if (type === 'folder') {
                window.location.href = 'index.php?path=' + encodeURIComponent(path);
            } else {
                previewFile(path);
            }
        }

        function previewFile(filePath) {
            const extension = filePath.split('.').pop().toLowerCase();
            const previewContent = document.getElementById('previewContent');
            const previewTitle = document.getElementById('previewTitle');
            
            previewTitle.textContent = 'Preview: ' + filePath.split('/').pop();
            
            // Show different content based on file type
            if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                previewContent.innerHTML = `<img src="uploads/${filePath}" class="file-preview" alt="Preview">`;
            } else if (['pdf'].includes(extension)) {
                previewContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file-pdf fa-3x mb-3"></i>
                        <p>PDF preview not available in browser. Please download the file.</p>
                        <a href="uploads/${filePath}" download class="btn btn-primary-custom">
                            <i class="fas fa-download"></i> Download PDF
                        </a>
                    </div>`;
            } else if (['txt', 'md'].includes(extension)) {
                // For text files, we would need to fetch the content via AJAX
                previewContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file-text fa-3x mb-3"></i>
                        <p>Text file preview not implemented. Please download the file.</p>
                        <a href="uploads/${filePath}" download class="btn btn-primary-custom">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>`;
            } else {
                previewContent.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-file fa-3x mb-3"></i>
                        <p>Preview not available for this file type.</p>
                        <a href="uploads/${filePath}" download class="btn btn-primary-custom">
                            <i class="fas fa-download"></i> Download File
                        </a>
                    </div>`;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('previewModal'));
            modal.show();
        }

        // UI Interaction functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('mobile-open');
            mainContent.classList.toggle('expanded');
        }

        function setGridView() {
            currentView = 'grid';
            document.getElementById('gridViewBtn').classList.add('active');
            document.getElementById('listViewBtn').classList.remove('active');
            document.getElementById('filesContainer').classList.remove('files-list');
            document.getElementById('filesContainer').classList.add('files-grid');
        }

        function setListView() {
            currentView = 'list';
            document.getElementById('listViewBtn').classList.add('active');
            document.getElementById('gridViewBtn').classList.remove('active');
            document.getElementById('filesContainer').classList.remove('files-grid');
            document.getElementById('filesContainer').classList.add('files-list');
        }

        function toggleView() {
            if (currentView === 'grid') {
                setListView();
                document.getElementById('viewIcon').className = 'fas fa-list';
            } else {
                setGridView();
                document.getElementById('viewIcon').className = 'fas fa-th';
            }
        }

        function filterFiles() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const fileCards = document.querySelectorAll('.file-card');
            
            fileCards.forEach(card => {
                const fileName = card.getAttribute('data-name');
                if (fileName.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Context Menu functions
        function showContextMenu(event, fileName, fileType, filePath) {
            event.preventDefault();
            selectedFile = { name: fileName, type: fileType, path: filePath };
            
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.style.display = 'block';
            contextMenu.style.left = event.pageX + 'px';
            contextMenu.style.top = event.pageY + 'px';
        }

        function hideContextMenu() {
            document.getElementById('contextMenu').style.display = 'none';
        }

        function showDropdownMenu(event, fileName, fileType, filePath) {
            event.stopPropagation();
            selectedFile = { name: fileName, type: fileType, path: filePath };
            
            const rect = event.target.getBoundingClientRect();
            const contextMenu = document.getElementById('contextMenu');
            contextMenu.style.display = 'block';
            contextMenu.style.left = (rect.left + window.scrollX) + 'px';
            contextMenu.style.top = (rect.bottom + window.scrollY) + 'px';
        }

        // File operation functions
        function downloadSelectedFile() {
            if (selectedFile.type !== 'folder') {
                window.location.href = 'download.php?file=' + selectedFile.path;
            } else {
                alert('Cannot download folders directly. Please select individual files.');
            }
            hideContextMenu();
        }

function getBaseUrl() {
     const lastPart = window.location.pathname.split("/").pop();
    const hasFile = lastPart.includes(".")

    if (hasFile) {
        return window.location.href.substring(0, window.location.href.lastIndexOf("/") + 1);
    } else {
        return window.location.href;
    }
}

        function copylinkSelectedFile() {
            
    if (selectedFile && selectedFile.type !== 'folder') {



        const fileUrl = getBaseUrl() + "uploads/" + selectedFile.path;

        navigator.clipboard.writeText(fileUrl)
            .then(() => {
                
            })
            .catch(err => {
                console.error("Failed to copy link:", err);
                alert("Could not copy link. Your browser may not support clipboard API.");
            });

    } else {
        alert('Cannot copy links for folders. Please select an individual file.');
    }

    hideContextMenu();
}


        function previewSelectedFile() {
            if (selectedFile.type !== 'folder') {
                previewFile(selectedFile.path);
            } else {
                alert('Cannot preview folders.');
            }
            hideContextMenu();
        }

        function showRenameModal() {
            document.getElementById('old_name').value = selectedFile.name;
            document.getElementById('new_name').value = selectedFile.name;
            const modal = new bootstrap.Modal(document.getElementById('renameModal'));
            modal.show();
            hideContextMenu();
        }

        function showMoveModal() {
            document.getElementById('file_to_move').value = selectedFile.name;
            const modal = new bootstrap.Modal(document.getElementById('moveModal'));
            modal.show();
            hideContextMenu();
        }

        function showCopyModal() {
            document.getElementById('file_to_copy').value = selectedFile.name;
            const modal = new bootstrap.Modal(document.getElementById('copyModal'));
            modal.show();
            hideContextMenu();
        }

        function deleteSelectedFile() {
            if (confirm(`Are you sure you want to delete "${selectedFile.name}"?`)) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'index.php<?php echo $currentPath ? "?path=" . urlencode($currentPath) : ""; ?>';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'file_path';
                input.value = selectedFile.path;
                form.appendChild(input);
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete';
                form.appendChild(deleteInput);
                
                document.body.appendChild(form);
                form.submit();
            }
            hideContextMenu();
        }

        // Utility functions
        function showFavorites() {
            alert('Favorites feature would be implemented with user sessions');
        }

        function showRecent() {
            alert('Recent files feature would track file access history');
        }

        function handleResize() {
            // Adjust UI for mobile
            if (window.innerWidth < 992) {
                document.getElementById('sidebar').classList.remove('mobile-open');
                document.getElementById('mainContent').classList.remove('expanded');
            }
        }
    </script>
</body>
</html>