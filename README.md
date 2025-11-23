# FileFlow - Modern PHP File Manager
<img width="1332" height="855" alt="image" src="https://github.com/user-attachments/assets/c5dbafc3-e670-48e0-9e32-d5b160ff3e15" />
<img width="1919" height="882" alt="image" src="https://github.com/user-attachments/assets/f272ec6b-ab55-48eb-81ce-6137516dd31d" />



A beautiful, modern, and fully functional file manager built with PHP, featuring a stunning dark theme with glass morphism effects and comprehensive file management capabilities.


## ✨ Features

### 🎨 Modern UI/UX
- **Dark Theme** with glass morphism effects
- **Responsive Design** that works on all devices
- **Smooth Animations** and hover effects
- **Beautiful Icons** for different file types
- **Grid & List View** toggle

### 📁 File Operations
- ✅ Upload multiple files
- ✅ Create, rename, and delete folders
- ✅ Move and copy files/folders
- ✅ Download files
- ✅ File preview (images)
- ✅ Search and filter files
- ✅ Breadcrumb navigation

### 🔐 Security
- **Authentication System** with session management
- **Session Timeout** (1 hour)
- **Secure File Operations**
- **Input Validation** and sanitization

### 📊 Dashboard
- **Storage Statistics** with visual progress bars
- **File Count** and folder statistics
- **Real-time Search**
- **Folder Tree** navigation

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Write permissions for uploads directory

### Installation

1. **Clone or Download the repository**
   ```bash
   git clone https://github.com/yourusername/fileflow.git
   cd fileflow
   ```
2. 📁 File Structure
 fileflow/
  ├── index.php              # Main file manager
  ├── login.php              # Login page
  ├── auth.php               # Authentication handler
  ├── session_manager.php    # Session management
  ├── file_operations.php    # All file operations
  ├── uploads/               # File storage directory
  └── README.md
3. 🔧 Configuration
   Changing Login Credentials
   Edit auth.php to change the default credentials:
     ```bash
     $valid_username = 'your-new-username';
     $valid_password = 'your-secure-password';
     ```
   
