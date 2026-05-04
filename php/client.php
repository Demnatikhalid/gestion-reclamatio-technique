<?php
session_start();
require 'config.php';

// Vérification de l'authentification et du rôle
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header('Location: login.php');
    exit();
}

// Récupérer la liste des appareils
$appareils = $conn->query("SELECT * FROM appareils")->fetchAll();

// Récupérer la liste des techniciens
$techniciens = $conn->query("SELECT * FROM users WHERE role = 'technicien'")->fetchAll();

// Traitement de la création de réclamation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appareil_id = $_POST['appareil_id'];
    $technicien_id = $_POST['technicien_id'];
    $description = $_POST['description'];

    try {
        $stmt = $conn->prepare("INSERT INTO reclamations (client_id, appareil_id, technicien_id, description, statut, date_creation) 
                              VALUES (:client_id, :appareil_id, :technicien_id, :description, 'en_attente', NOW())");
        
        if ($stmt->execute([
            'client_id' => $_SESSION['user']['id'],
            'appareil_id' => $appareil_id,
            'technicien_id' => $technicien_id,
            'description' => $description
        ])) {
            $_SESSION['success'] = "Réclamation créée avec succès!";
            header('Location: client.php');
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la création de la réclamation: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Client - Nouvelle Réclamation</title>
    <link rel="stylesheet" href="../css/styles.css">
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
            max-width: 800px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        
        .btn {
            padding: 12px 25px;
            background-color: #4e73df;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #3a5bc7;
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
        
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .container {
                padding: 0 15px;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-headset"></i> Espace Client</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <a href="client.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="client_history.php"><i class="fas fa-history"></i> Mes Réclamations</a>
                <a href="profile.php"><i class="fas fa-user-edit"></i> Mon Profil</a>
                <a href="login.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <h2 style="margin-top: 0; color: #4e73df;"><i class="fas fa-plus-circle"></i> Nouvelle Réclamation</h2>
            
            <form method="POST">
                <div class="form-group">
                    <label for="appareil_id"><i class="fas fa-laptop"></i> Appareil concerné</label>
                    <select id="appareil_id" name="appareil_id" class="form-control" required>
                        <?php foreach ($appareils as $appareil): ?>
                            <option value="<?= htmlspecialchars($appareil['id']) ?>">
                                <?= htmlspecialchars($appareil['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="technicien_id"><i class="fas fa-user-tie"></i> Technicien assigné</label>
                    <select id="technicien_id" name="technicien_id" class="form-control" required>
                        <?php foreach ($techniciens as $technicien): ?>
                            <option value="<?= htmlspecialchars($technicien['id']) ?>">
                                <?= htmlspecialchars($technicien['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-comment-alt"></i> Description du problème</label>
                    <textarea id="description" name="description" class="form-control" 
                              placeholder="Décrivez le problème en détail..." required></textarea>
                </div>
                
                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Envoyer la réclamation
                </button>
            </form>
        </div>
        
        <div class="card" style="text-align: center;">
            <p>Pour consulter l'historique de vos réclamations :</p>
            <a href="client_history.php" class="btn" style="display: inline-block;">
                <i class="fas fa-history"></i> Voir mes réclamations
            </a>
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