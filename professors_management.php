<?php
include('db.php');
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $idProf = $_POST['idProf'] ?? '';

    if ($action == 'add_professor') {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $specialite = $_POST['specialite'] ?? '';

        if (!empty($nom) && !empty($prenom) && !empty($specialite)) {
            $stmt = $conn->prepare("INSERT INTO prof (NomProf, prenomProf, specialite) VALUES (:nom, :prenom, :specialite)");
            $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'specialite' => $specialite]);

            $_SESSION['message'] = "Professeur ajouté avec succès.";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Veuillez remplir tous les champs.";
            $_SESSION['messageType'] = "error";
        }
    } elseif ($action == 'modify_professor') {
        $nom = $_POST['nom'] ?? '';
        $prenom = $_POST['prenom'] ?? '';
        $specialite = $_POST['specialite'] ?? '';

        if (!empty($nom) && !empty($prenom) && !empty($specialite)) {
            $stmt = $conn->prepare("UPDATE prof SET NomProf = :nom, prenomProf = :prenom, specialite = :specialite WHERE idProf = :idProf");
            $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'specialite' => $specialite, 'idProf' => $idProf]);

            $_SESSION['message'] = "Professeur modifié avec succès.";
            $_SESSION['messageType'] = "success";
        } else {
            $_SESSION['message'] = "Veuillez remplir tous les champs.";
            $_SESSION['messageType'] = "error";
        }
    } elseif ($action == 'delete_professor') {
        // First, delete from the enseigne table where the foreign key matches the idProf
        $stmt = $conn->prepare("DELETE FROM enseigne WHERE idProf = :idProf");
        $stmt->execute(['idProf' => $idProf]);
    
        // Then, delete from the prof table
        $stmt = $conn->prepare("DELETE FROM prof WHERE idProf = :idProf");
        $stmt->execute(['idProf' => $idProf]);
    
        $_SESSION['message'] = "Professeur supprimé avec succès.";
        $_SESSION['messageType'] = "success";
    }

    header("Location: professors_management.php");
    exit;
}

$profsStmt = $conn->query("SELECT * FROM prof ORDER BY NomProf, prenomProf");
$profs = $profsStmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message'], $_SESSION['messageType']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Professeurs</title>
    <link rel="stylesheet" href="css/StudentListeStyle.css">
    <link rel="stylesheet" href="css/all.min.css">

    <script>
        // Hide message after 3 seconds
        window.onload = function() {
            setTimeout(function() {
                const messageDiv = document.querySelector('.message');
                if (messageDiv) {
                    messageDiv.style.display = 'none';
                }
            }, 3000);
        };
    </script>
</head>
<body>
    <a href="index.php" class="back-button"><i class="fa-solid fa-arrow-left"></i></a>

    <?php if (!empty($message)) : ?>
        <div class="message <?= $messageType ?>" style="display: block;"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <br>
    <h1>Gestion des Professeurs</h1>
    <br>

    <button class="add-course-button" onclick="showModifyForm()"><i class="fa-solid fa-user-plus"></i></button>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Spécialité</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profs as $prof) : ?>
                    <tr>
                        <td><?= htmlspecialchars($prof['NomProf']) ?></td>
                        <td><?= htmlspecialchars($prof['prenomProf']) ?></td>
                        <td><?= htmlspecialchars($prof['specialite']) ?></td>
                        <td>
                            <button onclick="showModifyForm('<?= $prof['idProf'] ?>', '<?= htmlspecialchars($prof['NomProf']) ?>', '<?= htmlspecialchars($prof['prenomProf']) ?>', '<?= htmlspecialchars($prof['specialite']) ?>')"><i class="fa-solid fa-wrench"></i></button>
                            <button class="delete-button" onclick="confirmDeletion('<?= $prof['idProf'] ?>', '<?= htmlspecialchars($prof['NomProf'] . ' ' . $prof['prenomProf']) ?>')"><i class="fa-solid fa-user-xmark"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Formulaire pour ajouter/modifier un professeur -->
    <div id="modifyForm" class="modal">
        <div class="modal-content">
            <h2>Ajouter / Modifier un Professeur</h2>
            <form action="professors_management.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add_professor">
                <input type="hidden" name="idProf" id="modifyIdProf">
                <label for="nom">Nom :</label>
                <input type="text" name="nom" id="modifyNom" required><br><br>
                <label for="prenom">Prénom :</label>
                <input type="text" name="prenom" id="modifyPrenom" required><br><br>
                <label for="specialite">Spécialité :</label>
                <input type="text" name="specialite" id="modifySpecialite" required><br><br>

                <button type="submit">Enregistrer</button>
            </form>
            <button class="cancel-button" onclick="hideModifyForm()">Annuler</button>
        </div>
    </div>

    <!-- Formulaire de confirmation de suppression -->
    <div id="deleteForm" class="modal">
        <div class="modal-content">
            <h2>Confirmation de suppression</h2>
            <p id="deleteMessage"></p>
            <form action="professors_management.php" method="POST">
                <input type="hidden" name="action" value="delete_professor">
                <input type="hidden" name="idProf" id="deleteIdProf">
                <button type="submit">Confirmer</button>
            </form>
            <button class="cancel-button" onclick="hideDeleteForm()">Annuler</button>
        </div>
    </div>

    <script>
        function showModifyForm(idProf = '', nom = '', prenom = '', specialite = '') {
            document.getElementById('modifyIdProf').value = idProf;
            document.getElementById('modifyNom').value = nom;
            document.getElementById('modifyPrenom').value = prenom;
            document.getElementById('modifySpecialite').value = specialite;

            if (idProf) {
                document.getElementById('formAction').value = 'modify_professor';
            } else {
                document.getElementById('formAction').value = 'add_professor';
            }

            document.getElementById('modifyForm').style.display = 'block';
        }

        function hideModifyForm() {
            document.getElementById('modifyForm').style.display = 'none';
        }

        function confirmDeletion(idProf, profName) {
            document.getElementById('deleteIdProf').value = idProf;
            document.getElementById('deleteMessage').innerText = `Êtes-vous sûr de vouloir supprimer le professeur ${profName} ?`;
            document.getElementById('deleteForm').style.display = 'block';
        }

        function hideDeleteForm() {
            document.getElementById('deleteForm').style.display = 'none';
        }
    </script>
</body>
</html>
