<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Récupérer toutes les réclamations
$stmt = $conn->query("SELECT r.*, a.nom AS appareil_nom, 
                      uc.username AS client_nom, ut.username AS technicien_nom
                      FROM reclamations r
                      JOIN appareils a ON r.appareil_id = a.id
                      JOIN users uc ON r.client_id = uc.id
                      JOIN users ut ON r.technicien_id = ut.id
                      ORDER BY r.date_creation DESC");
$reclamations = $stmt->fetchAll();

// Récupérer les statistiques
$stats = $conn->query("SELECT 
                        COUNT(*) AS total,
                        SUM(statut = 'en_attente') AS en_attente,
                        SUM(statut = 'en_cours') AS en_cours,
                        SUM(statut = 'termine') AS termine
                      FROM reclamations")->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Administrateur</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .user-menu-container {
            position: relative;
        }
        .user-icon {
            font-size: 24px;
            color: #495057;
            cursor: pointer;
            transition: all 0.3s;
        }
        .user-icon:hover {
            color: #007bff;
            transform: scale(1.1);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .dropdown-menu.show {
            display: block;
        }
        .user-info {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
            color: #d63384;
        }
        .dropdown-menu a {
            display: block;
            padding: 8px 0;
            color: #333;
            text-decoration: none;
            transition: all 0.2s;
        }
        .dropdown-menu a:hover {
            color: #007bff;
            background-color: #f8f9fa;
        }
        .dropdown-menu a i {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }
        .logout-link {
            color: #dc3545 !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-shield"></i> Espace Administrateur</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-cog user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <div class="user-info">
                    <strong><i class="fas fa-crown"></i> <?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
                </div>
                <a href="adminprofile.php"><i class="fas fa-user-edit"></i> Mon profil</a>
                <a href="admin_users.php"><i class="fas fa-users-cog"></i> Gestion utilisateurs</a>
                <a href="login.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="stats-container">
            <div class="stat-card total">
                <h3><i class="fas fa-tasks"></i> Total</h3>
                <p><?= htmlspecialchars($stats['total']) ?></p>
            </div>
            <div class="stat-card pending">
                <h3><i class="fas fa-clock"></i> En attente</h3>
                <p><?= htmlspecialchars($stats['en_attente']) ?></p>
            </div>
            <div class="stat-card in-progress">
                <h3><i class="fas fa-spinner"></i> En cours</h3>
                <p><?= htmlspecialchars($stats['en_cours']) ?></p>
            </div>
            <div class="stat-card completed">
                <h3><i class="fas fa-check-circle"></i> Terminées</h3>
                <p><?= htmlspecialchars($stats['termine']) ?></p>
            </div>
        </div>

        <div class="table-container">
            <h2><i class="fas fa-list"></i> Toutes les réclamations</h2>
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> ID</th>
                        <th><i class="fas fa-laptop"></i> Appareil</th>
                        <th><i class="fas fa-user"></i> Client</th>
                        <th><i class="fas fa-user-tie"></i> Technicien</th>
                        <th><i class="fas fa-comment-alt"></i> Description</th>
                        <th><i class="fas fa-info-circle"></i> Statut</th>
                        <th><i class="fas fa-calendar-plus"></i> Création</th>
                        <th><i class="fas fa-calendar-check"></i> Résolution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reclamations as $reclamation): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($reclamation['id']) ?></td>
                            <td><?= htmlspecialchars($reclamation['appareil_nom']) ?></td>
                            <td><?= htmlspecialchars($reclamation['client_nom']) ?></td>
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