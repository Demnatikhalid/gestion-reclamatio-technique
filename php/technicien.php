<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'technicien') {
    header('Location: login.php');
    exit();
}

// Récupérer les réclamations assignées
$stmt = $conn->prepare("SELECT r.*, a.nom AS appareil_nom, u.username AS client_nom 
                        FROM reclamations r
                        JOIN appareils a ON r.appareil_id = a.id
                        JOIN users u ON r.client_id = u.id
                        WHERE technicien_id = :technicien_id
                        ORDER BY CASE 
                            WHEN statut = 'en_attente' THEN 1
                            WHEN statut = 'en_cours' THEN 2
                            ELSE 3
                        END, date_creation DESC");
$stmt->execute(['technicien_id' => $_SESSION['user']['id']]);
$reclamations = $stmt->fetchAll();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reclamation_id = $_POST['reclamation_id'];
    $action = $_POST['action'];

    if ($action == 'valider') {
        $stmt = $conn->prepare("UPDATE reclamations SET statut = 'en_cours' WHERE id = :id");
        $stmt->execute(['id' => $reclamation_id]);
    } elseif ($action == 'terminer') {
        $stmt = $conn->prepare("UPDATE reclamations SET statut = 'termine', date_resolution = NOW() WHERE id = :id");
        $stmt->execute(['id' => $reclamation_id]);
    }
    header('Location: technicien.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Technicien</title>
    <link rel="stylesheet" href="../css/tech.css">
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
            color: #2c7be5;
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
        <h1><i class="fas fa-tools"></i> Espace Technicien</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-shield user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <div class="user-info">
                    <strong><i class="fas fa-user-tie"></i> <?= htmlspecialchars($_SESSION['user']['username']) ?></strong>
                </div>
                <a href="tech_profile.php"><i class="fas fa-tools"></i> Mon espace</a>
                <a href="login.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> ID</th>
                        <th><i class="fas fa-laptop"></i> Appareil</th>
                        <th><i class="fas fa-user"></i> Client</th>
                        <th><i class="fas fa-comment-alt"></i> Description</th>
                        <th><i class="fas fa-info-circle"></i> Statut</th>
                        <th><i class="fas fa-calendar-plus"></i> Date création</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reclamations as $reclamation): ?>
                        <tr>
                            <td>#<?= htmlspecialchars($reclamation['id']) ?></td>
                            <td><?= htmlspecialchars($reclamation['appareil_nom']) ?></td>
                            <td><?= htmlspecialchars($reclamation['client_nom']) ?></td>
                            <td><?= htmlspecialchars($reclamation['description']) ?></td>
                            <td class="status-<?= htmlspecialchars($reclamation['statut']) ?>">
                                <?= htmlspecialchars($reclamation['statut']) ?>
                            </td>
                            <td><?= htmlspecialchars($reclamation['date_creation']) ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="reclamation_id" value="<?= htmlspecialchars($reclamation['id']) ?>">
                                    <?php if ($reclamation['statut'] == 'en_attente'): ?>
                                        <button type="submit" name="action" value="valider" class="btn-validate">
                                            <i class="fas fa-check-circle"></i> Valider
                                        </button>
                                    <?php elseif ($reclamation['statut'] == 'en_cours'): ?>
                                        <button type="submit" name="action" value="terminer" class="btn-complete">
                                            <i class="fas fa-check-double"></i> Terminer
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
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