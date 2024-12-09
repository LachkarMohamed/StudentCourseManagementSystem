<?php
include('db.php');
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $idEt = $_POST['idEt'];
    $idCours = $_POST['idCours'];

    $stmt = $conn->prepare("DELETE FROM inscription WHERE idEt = :idEt AND idCours = :idCours");
    $stmt->bindParam(':idEt', $idEt);
    $stmt->bindParam(':idCours', $idCours);
    $stmt->execute();

    $_SESSION['message'] = "L'étudiant a été supprimé avec succès.";
    $_SESSION['messageType'] = "success";

    header("Location: view_delete.php");
    exit;
}

$stmt = $conn->query("
    SELECT c.idCategorie, c.NomCategorie, m.idMatiere, m.NomMatiere, COUNT(i.idEt) AS studentCount
    FROM categorie c
    LEFT JOIN matiere m ON c.idCategorie = m.idCategorie
    LEFT JOIN cours co ON m.idMatiere = co.idMatiere
    LEFT JOIN inscription i ON co.idCours = i.idCours
    GROUP BY c.idCategorie, c.NomCategorie, m.idMatiere, m.NomMatiere
    ORDER BY c.NomCategorie, m.NomMatiere
");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$studentStmt = $conn->query("
    SELECT e.idEt, e.NomEt, e.PrenomEt, co.idCours, m.idMatiere, m.NomMatiere
    FROM etudiant e
    JOIN inscription i ON e.idEt = i.idEt
    JOIN cours co ON i.idCours = co.idCours
    JOIN matiere m ON co.idMatiere = m.idMatiere
");
$students = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

$studentData = [];
foreach ($students as $student) {
    $studentData[$student['idMatiere']][] = $student;
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['messageType'];
    unset($_SESSION['message']);
    unset($_SESSION['messageType']);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des matieres</title>
    <link rel="stylesheet" href="css/viewStyle.css">
    <link rel="stylesheet" href="css/all.min.css">

    <script>
        function confirmDeletion(idEt, idCours, studentName, courseName) {
            document.getElementById('deleteStudentName').textContent = `Êtes-vous sûr de vouloir supprimer ${studentName} du cours ${courseName} ?`;
            document.getElementById('deleteStudentIdEt').value = idEt;
            document.getElementById('deleteStudentIdCours').value = idCours;
            document.getElementById('deleteConfirmationModal').style.display = 'block';
        }

        function hideDeleteConfirmation() {
            document.getElementById('deleteConfirmationModal').style.display = 'none';
        }

        function confirmDelete() {
            document.getElementById('deleteForm').submit();
        }

        window.onload = function() {
            var message = document.querySelector('.message');
            if (message) {
                message.style.display = 'block';
                setTimeout(function() {
                    message.style.display = 'none';
                }, 3000);
            }
        };
    </script>
</head>

<body>
    <a href="index.php" class="back-button"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="message <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
    </div>

    <h1>Gestion des matieres</h1>

    <div class="categories">
        <?php
        $currentCategory = null;
        foreach ($subjects as $subject) {
            if ($currentCategory !== $subject['NomCategorie']) {
                if ($currentCategory !== null) echo "</div>";
                echo "<div class='category'>";
                echo "<h2 class='category-title'>{$subject['NomCategorie']}</h2><div class='subjects'>";
                $currentCategory = $subject['NomCategorie'];
            }
            echo "<div class='subject'>";
            echo "<h3 class='subject-title'>{$subject['NomMatiere']} (Étudiants : {$subject['studentCount']})</h3>";
            echo "<table>";
            echo "<tr><th>Nom</th><th>Prénom</th><th>Action</th></tr>";
            if (!empty($studentData[$subject['idMatiere']])) {
                foreach ($studentData[$subject['idMatiere']] as $student) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($student['NomEt']) . "</td>";
                    echo "<td>" . htmlspecialchars($student['PrenomEt']) . "</td>";
                    echo "<td><button onclick=\"confirmDeletion('" . $student['idEt'] . "', '" . $student['idCours'] . "', '" . htmlspecialchars($student['NomEt']) . "', '" . htmlspecialchars($subject['NomMatiere']) . "')\"><i class='fa-solid fa-trash-can'></i></button></td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>Aucun étudiant inscrit.</td></tr>";
            }
            echo "</table></div>";
        }
        ?>
    </div>

    <div id="deleteConfirmationModal" class="modal">
        <div class="modal-content">
            <h2>Confirmer la Suppression</h2>
            <p id="deleteStudentName"></p>
            <button onclick="confirmDelete()">Supprimer</button>
            <button onclick="hideDeleteConfirmation()">Annuler</button>
        </div>
    </div>

    <form id="deleteForm" action="view_delete.php" method="POST" style="display: none;">
        <input type="hidden" name="idEt" id="deleteStudentIdEt">
        <input type="hidden" name="idCours" id="deleteStudentIdCours">
    </form>
</body>

</html>