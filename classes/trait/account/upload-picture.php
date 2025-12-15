<?php  

trait AccountProfileTrait {
    private $upload_dir = 'uploads/profilepics/';
    private $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private $max_file_size = 5242880;  

    public function __construct() {
        $this->connect();
         
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    } 

    public function updateProfilePicture(int $account_ID, array $file): array {
        try {
            // Validate file upload
            if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
                $this->setLastError('No file uploaded or upload error occurred');
                return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
            }

            // Check file size
            if ($file['size'] > $this->max_file_size) {
                $this->setLastError('File size exceeds 5MB limit');
                return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
            }

            // Check file type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime_type, $this->allowed_types)) {
                $this->setLastError('Invalid file type. Only JPEG, PNG, GIF, and WebP allowed');
                return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
            }

            // Get old profile picture to delete it
            $stmt = $this->conn->prepare("SELECT account_profilepic FROM Account_Info WHERE account_ID = ?");
            $stmt->execute([$account_ID]);
            $old_pic = $stmt->fetch();

            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'profile_' . $account_ID . '_' . time() . '.' . $file_extension;
            $file_path = $this->upload_dir . $new_filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                $this->setLastError('Failed to save file');
                return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
            }

            // Update database
            $stmt = $this->conn->prepare("UPDATE Account_Info SET account_profilepic = ? WHERE account_ID = ?");
            
            if ($stmt->execute([$file_path, $account_ID])) {
                // Delete old profile picture if exists
                if ($old_pic && $old_pic['account_profilepic'] && file_exists($old_pic['account_profilepic'])) {
                    unlink($old_pic['account_profilepic']);
                }
                
                return [
                    'success' => true, 
                    'message' => 'Profile picture updated successfully', 
                    'file_path' => $file_path
                ];
            } else {
                // Remove uploaded file if database update fails
                unlink($file_path);
                $this->setLastError('Database update failed');
                return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
            }

        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
        } catch (Exception $e) {
            $this->setLastError('Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError(), 'file_path' => null];
        }
    }
 
    public function deleteProfilePicture(int $account_ID): array {
        try {
            // Get current profile picture
            $stmt = $this->conn->prepare("SELECT account_profilepic FROM Account_Info WHERE account_ID = ?");
            $stmt->execute([$account_ID]);
            $account = $stmt->fetch();

            if (!$account) {
                $this->setLastError('Account not found');
                return ['success' => false, 'message' => $this->getLastError()];
            }

            // Update database to NULL
            $stmt = $this->conn->prepare("UPDATE Account_Info SET account_profilepic = NULL WHERE account_ID = ?");
            
            if ($stmt->execute([$account_ID])) {
                // Delete physical file if exists
                if ($account['account_profilepic'] && file_exists($account['account_profilepic'])) {
                    unlink($account['account_profilepic']);
                }
                
                return ['success' => true, 'message' => 'Profile picture deleted successfully'];
            } else {
                $this->setLastError('Failed to delete profile picture from database');
                return ['success' => false, 'message' => $this->getLastError()];
            }

        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        } catch (Exception $e) {
            $this->setLastError('Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $this->getLastError()];
        }
    }
 
    public function getProfilePicture(int $account_ID): ?string {
        try {
            $stmt = $this->conn->prepare("SELECT account_profilepic FROM Account_Info WHERE account_ID = ?");
            $stmt->execute([$account_ID]);
            $account = $stmt->fetch();
            
            return $account ? $account['account_profilepic'] : null;
        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return null;
        }
    }
 
    public function accountExists(int $account_ID): bool {
        try {
            $stmt = $this->conn->prepare("SELECT account_ID FROM Account_Info WHERE account_ID = ?");
            $stmt->execute([$account_ID]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->setLastError('Database error: ' . $e->getMessage());
            return false;
        }
    }
}

?>