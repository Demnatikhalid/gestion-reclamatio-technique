<?php
session_start();
require 'config.php';

// Vérification de l'admin connecté
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['user']['role'] != 'admin') {
    header('HTTP/1.0 403 Forbidden');
    echo "Accès interdit";
    exit();
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ajout d'un nouvel utilisateur
    if (isset($_POST['add_user'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password']; // Mot de passe en clair
        $role = $_POST['role'];
        
        // Validation des champs
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Le nom d'utilisateur est requis";
        } elseif (strlen($username) < 4) {
            $errors[] = "Le nom d'utilisateur doit contenir au moins 4 caractères";
        }
        
        if (empty($email)) {
            $errors[] = "L'adresse email est requise";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "L'adresse email n'est pas valide";
        }
        
        if (empty($password)) {
            $errors[] = "Le mot de passe est requis";
        } elseif (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins une majuscule";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Le mot de passe doit contenir au moins un chiffre";
        }
        
        if (empty($errors)) {
            // Stockage du mot de passe en clair (NON RECOMMANDE)
            $plain_password = $password;

            // Vérification de l'existence de l'utilisateur
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute(['username' => $username, 'email' => $email]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Ce nom d'utilisateur ou email est déjà utilisé";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (:username, :email, :password, :role, NOW())");
                if ($stmt->execute([
                    'username' => $username,
                    'email' => $email,
                    'password' => $plain_password, // Stockage en clair
                    'role' => $role
                ])) {
                    // Journalisation de l'action
                    $log_message = "Nouvel utilisateur créé: " . $username . " (Rôle: " . $role . ")";
                    error_log($log_message);
                    
                    $_SESSION['success'] = "Utilisateur ajouté avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de l'ajout de l'utilisateur";
                }
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
    // Suppression d'un utilisateur
    elseif (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        
        // Empêcher la suppression de l'admin principal
        if ($user_id == 1) {
            $_SESSION['error'] = "Impossible de supprimer l'administrateur principal";
        } else {
            // Récupérer les infos avant suppression pour le log
            $stmt = $conn->prepare("SELECT username FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
            if ($stmt->execute(['id' => $user_id])) {
                // Journalisation de l'action
                $log_message = "Utilisateur supprimé: " . $user['username'] . " (ID: " . $user_id . ")";
                error_log($log_message);
                
                $_SESSION['success'] = "Utilisateur supprimé avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la suppression";
            }
        }
    }
    // Mise à jour du rôle
    elseif (isset($_POST['update_role'])) {
        $user_id = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        
        // Validation du rôle
        $allowed_roles = ['admin', 'technicien', 'client'];
        if (!in_array($new_role, $allowed_roles)) {
            $_SESSION['error'] = "Rôle invalide";
        }
        // Empêcher la modification de l'admin principal
        elseif ($user_id == 1) {
            $_SESSION['error'] = "Impossible de modifier le rôle de l'administrateur principal";
        } else {
            // Récupérer l'ancien rôle pour le log
            $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch();
            
            $stmt = $conn->prepare("UPDATE users SET role = :role WHERE id = :id");
            if ($stmt->execute(['role' => $new_role, 'id' => $user_id])) {
                // Journalisation de l'action
                $log_message = "Changement de rôle pour " . $user['username'] . ": " . $user['role'] . " → " . $new_role;
                error_log($log_message);
                
                $_SESSION['success'] = "Rôle mis à jour avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la mise à jour du rôle";
            }
        }
    }

    header('Location: admin_users.php');
    exit();
}

// Récupérer la liste des utilisateurs avec pagination
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT SQL_CALC_FOUND_ROWS id, username, email, role, created_at FROM users ORDER BY role, username LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll();

$total_users = $conn->query("SELECT FOUND_ROWS()")->fetchColumn();
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs</title>
    <link rel="stylesheet" href="../css/adminuser.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background-color: #f8f9fa;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .select-control {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            background-color: white;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: #4e73df;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3a5bc7;
        }
        
        .btn-danger {
            background-color: #e74a3b;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #d62c1a;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .role-admin {
            color: #4e73df;
            font-weight: 600;
        }
        
        .role-technicien {
            color: #1cc88a;
            font-weight: 600;
        }
        
        .role-client {
            color: #6c757d;
        }
        
        .user-menu-container {
            position: relative;
        }
        
        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 10px;
            min-width: 200px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .dropdown-menu.show {
            display: block;
        }
        
        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .dropdown-menu a:hover {
            color: #4e73df;
            background-color: #f8f9fa;
        }
        
        .user-icon {
            font-size: 24px;
            color: #495057;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-icon:hover {
            color: #4e73df;
            transform: scale(1.1);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }
        
        .pagination a {
            padding: 8px 16px;
            margin: 0 4px;
            border: 1px solid #ddd;
            text-decoration: none;
            color: #4e73df;
            border-radius: 4px;
        }
        
        .pagination a.active {
            background-color: #4e73df;
            color: white;
            border: 1px solid #4e73df;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .card-header, .card-body {
                padding: 15px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
        
        .security-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 5px solid #ffeeba;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-cog user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <a href="admin.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="adminprofile.php"><i class="fas fa-user-edit"></i> Mon profil</a>
                <a href="login.php" style="color: #e74a3b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-plus"></i> Ajouter un nouvel utilisateur</h2>
            </div>
            <div class="container">
            <div class="card-body">
                <form method="POST" onsubmit="return validatePassword()">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" name="username" class="form-control" required minlength="4">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                        <small class="text-muted">Minimum 8 caractères, dont une majuscule et un chiffre</small>
                        <div id="password-strength" style="margin-top:5px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <select id="role" name="role" class="select-control" required>
                            <option value="admin">Administrateur</option>
                            <option value="technicien">Technicien</option>
                            <option value="client" selected>Client</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="add_user" class="btn btn-primary">
                        <i class="fas fa-save"></i> Ajouter l'utilisateur
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users"></i> Liste des utilisateurs (<?= $total_users ?> total)</h2>
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom d'utilisateur</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date création</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                    <td>
                                        <span class="role-<?= htmlspecialchars($user['role']) ?>">
                                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                                    <td>
                                        <form method="POST" style="display: inline-block;">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                            <select name="new_role" onchange="this.form.submit()" 
                                                <?= $user['id'] == 1 ? 'disabled' : '' ?>
                                                style="padding: 5px; border-radius: 4px; border: 1px solid #ddd;">
                                                <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                                <option value="technicien" <?= $user['role'] == 'technicien' ? 'selected' : '' ?>>Technicien</option>
                                                <option value="client" <?= $user['role'] == 'client' ? 'selected' : '' ?>>Client</option>
                                            </select>
                                            <input type="hidden" name="update_role" value="1">
                                        </form>
                                        
                                        <form method="POST" style="display: inline-block; margin-left: 5px;">
                                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                            <button type="submit" name="delete_user" class="btn btn-danger btn-sm"
                                                <?= $user['id'] == 1 ? 'disabled' : '' ?>
                                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur? Cette action est irréversible.')">
                                                <i class="fas fa-trash-alt"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>">&laquo;</a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" <?= $i == $page ? 'class="active"' : '' ?>><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>">&raquo;</a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gestion du menu utilisateur
        document.getElementById('userIcon').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userMenu').classList.toggle('show');
        });

        // Fermer le menu quand on clique ailleurs
        window.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu-container')) {
                document.getElementById('userMenu').classList.remove('show');
            }
        });

        // Validation du mot de passe côté client
        function validatePassword() {
            const password = document.getElementById('password').value;
            const errors = [];
            
            if (password.length < 8) {
                errors.push("Le mot de passe doit contenir au moins 8 caractères");
            }
            
            if (!/[A-Z]/.test(password)) {
                errors.push("Le mot de passe doit contenir au moins une majuscule");
            }
            
            if (!/[0-9]/.test(password)) {
                errors.push("Le mot de passe doit contenir au moins un chiffre");
            }
            
            if (errors.length > 0) {
                alert(errors.join("\n"));
                return false;
            }
            
            return true;
        }

        // Indicateur de force du mot de passe
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            const colors = ['#ff0000', '#ff5e00', '#ffbb00', '#a4ff00', '#00ff00'];
            const texts = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
            
            strengthBar.style.color = colors[strength];
            strengthBar.textContent = "Force du mot de passe: " + texts[strength];
        });
    </script>
</body>
</html>