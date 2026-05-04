<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$admin_id = $_SESSION['user']['id'];

// Récupération des données admin
$stmt = $conn->prepare("SELECT id, username, email, created_at FROM users WHERE id = ?");
$stmt->execute([$admin_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistiques
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_tech' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'technicien'")->fetchColumn(),
    'total_clients' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn(),
    'open_tickets' => $conn->query("SELECT COUNT(*) FROM reclamations WHERE statut = 'ouvert'")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Profil Administrateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4e73df;
            --danger: #e74a3b;
            --success: #1cc88a;
            --warning: #f6c23e;
            --dark: #5a5c69;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f8f9fc;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 30px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
            color: var(--primary);
            background-color: #f8f9fa;
        }
        .logout-link {
            color: var(--danger) !important;
        }
        .user-icon {
            font-size: 24px;
            color: #495057;
            cursor: pointer;
            transition: all 0.3s;
        }
        .user-icon:hover {
            color: var(--primary);
            transform: scale(1.1);
        }
        .profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            padding: 30px;
            margin-bottom: 30px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: bold;
            margin-right: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-left: 5px solid var(--primary);
        }
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.warning { border-left-color: var(--warning); }
        .stat-card.danger { border-left-color: var(--danger); }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: var(--dark);
        }
        .stat-label {
            color: #858796;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: bold;
        }
        .info-group {
            margin-bottom: 20px;
        }
        .info-label {
            color: #858796;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            padding: 8px 0;
        }
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .quick-action {
            background: var(--primary);
            color: white;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        .quick-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .quick-action.success { background: var(--success); }
        .quick-action.warning { background: var(--warning); }
        .quick-action.danger { background: var(--danger); }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-user-shield"></i> Profil Administrateur</h1>
        <div class="user-menu-container">
            <i class="fas fa-user-circle user-icon" id="userIcon"></i>
            <div class="dropdown-menu" id="userMenu">
                <a href="admin.php"><i class="fas fa-home"></i> Accueil</a>
                <a href="admin_users.php"><i class="fas fa-users-cog"></i> Gestion Utilisateurs</a>
                <a href="profile.php"><i class="fas fa-user-edit"></i> Mon Profil</a>
                <a href="login.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
    </div>

    <div class="profile-container">
        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <div class="stat-value"><?= $stats['total_users'] ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?= $stats['total_tech'] ?></div>
                <div class="stat-label">Techniciens</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?= $stats['total_clients'] ?></div>
                <div class="stat-label">Clients</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-value"><?= $stats['open_tickets'] ?></div>
                <div class="stat-label">Tickets ouverts</div>
            </div>
        </div>

        <!-- Profil Admin -->
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <?= strtoupper(substr($admin['username'], 0, 1)) ?>
                </div>
                <div>
                    <h2><?= htmlspecialchars($admin['username']) ?></h2>
                    <p>Administrateur système - Accès complet</p>
                </div>
            </div>

            <div class="profile-details">
                <div class="info-group">
                    <div class="info-label"><i class="fas fa-id-card"></i> ID Administrateur</div>
                    <div class="info-value"><?= $admin['id'] ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label"><i class="fas fa-user"></i> Nom d'utilisateur</div>
                    <div class="info-value"><?= htmlspecialchars($admin['username']) ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="info-value"><?= htmlspecialchars($admin['email']) ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Date de création</div>
                    <div class="info-value"><?= date('d/m/Y', strtotime($admin['created_at'])) ?></div>
                </div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="profile-card">
            <h3><i class="fas fa-bolt"></i> Actions rapides</h3>
            <div class="quick-actions">
                <a href="admin_users.php?action=create" class="quick-action success">
                    <i class="fas fa-user-plus"></i> Créer un utilisateur
                </a>
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
    </script>
</body>
</html>