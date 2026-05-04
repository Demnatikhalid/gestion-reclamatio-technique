<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user']['id'];
$current_role = $_SESSION['user']['role'];

// Récupération sécurisée des données utilisateur
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Initialisation des valeurs
$user['username'] = $user['username'] ?? '';
$user['email'] = $user['email'] ?? '';
$user['role'] = $user['role'] ?? 'client';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Profil</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #e6f0ff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #4e73df;
            font-weight: bold;
            margin-right: 20px;
        }
        
        .info-group {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-label {
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
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
        
        .logout-link {
            color: #e74a3b !important;
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
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user"></i> Mon Profil</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <a href="client.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="client_history.php"><i class="fas fa-history"></i> Mes Réclamations</a>
                <a href="login.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?= strtoupper(substr(htmlspecialchars($user['username']), 0, 1)) ?>
            </div>
            <h2><?= htmlspecialchars($user['username']) ?></h2>
        </div>

        <div class="info-group">
            <div class="info-label"><i class="fas fa-user"></i> Nom d'utilisateur</div>
            <div class="info-value"><?= htmlspecialchars($user['username']) ?></div>
        </div>

        <div class="info-group">
            <div class="info-label"><i class="fas fa-envelope"></i> Adresse email</div>
            <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>

        <div class="info-group">
            <div class="info-label"><i class="fas fa-user-tag"></i> Rôle</div>
            <div class="info-value"><?= htmlspecialchars(ucfirst($user['role'])) ?></div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <p>Pour modifier vos informations, veuillez contacter l'administrateur.</p>
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
    </script>
</body>
</html>