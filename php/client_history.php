<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'client') {
    header('Location: login.php');
    exit();
}

// Récupérer l'historique des réclamations du client
$stmt = $conn->prepare("SELECT r.*, a.nom AS appareil_nom, u.username AS technicien_nom 
                       FROM reclamations r 
                       JOIN appareils a ON r.appareil_id = a.id 
                       JOIN users u ON r.technicien_id = u.id 
                       WHERE client_id = :client_id 
                       ORDER BY date_creation DESC");
$stmt->execute(['client_id' => $_SESSION['user']['id']]);
$historique_reclamations = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon Historique</title>
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
            background-color: #4e73df;
            color: white;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background-color: #f8f9fa;
            color: #555;
        }
        
        .status-en_attente {
            color: #e67e22;
            font-weight: 600;
        }
        
        .status-en_cours {
            color: #2c7be5;
            font-weight: 600;
        }
        
        .status-termine {
            color: #27ae60;
            font-weight: 600;
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
            padding: 8px 0;
            color: #333;
            text-decoration: none;
        }
        
        .dropdown-menu a:hover {
            color: #4e73df;
        }
        
        .user-icon {
            font-size: 24px;
            color: #495057;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-history"></i> Mon Historique de Réclamations</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <a href="client.php"><i class="fas fa-home"></i> Tableau de bord</a>
                <a href="client_history.php"><i class="fas fa-history"></i> Mon historique</a>
                <a href="profile.php"><i class="fas fa-user-edit"></i> Mon profil</a>
                <a href="login.php" style="color: #e74a3b;"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Toutes mes réclamations</h2>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Appareil</th>
                            <th>Technicien</th>
                            <th>Description</th>
                            <th>Statut</th>
                            <th>Date création</th>
                            <th>Date résolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historique_reclamations as $reclamation): ?>
                            <tr>
                                <td><?= htmlspecialchars($reclamation['appareil_nom']) ?></td>
                                <td><?= htmlspecialchars($reclamation['technicien_nom']) ?></td>
                                <td><?= htmlspecialchars($reclamation['description']) ?></td>
                                <td class="status-<?= htmlspecialchars($reclamation['statut']) ?>">
                                    <?= htmlspecialchars($reclamation['statut']) ?>
                                </td>
                                <td><?= htmlspecialchars($reclamation['date_creation']) ?></td>
                                <td><?= !empty($reclamation['date_resolution']) ? htmlspecialchars($reclamation['date_resolution']) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('userIcon').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('userMenu').classList.toggle('show');
        });

        window.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu-container')) {
                document.getElementById('userMenu').classList.remove('show');
            }
        });
    </script>
</body>
</html>