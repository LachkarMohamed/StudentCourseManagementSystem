<?php
include('db.php');
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $idEt = $_POST['idEt'];

        if ($action == 'add_to_course') {
            $idCours = $_POST['idCours'];
            $DateInscri = !empty($_POST['DateInscri']) ? $_POST['DateInscri'] : NULL;

            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM inscription WHERE idEt = :idEt AND idCours = :idCours");
            $checkStmt->execute(['idEt' => $idEt, 'idCours' => $idCours]);
            $isEnrolled = $checkStmt->fetchColumn() > 0;

            if (!$isEnrolled) {
                $stmt = $conn->prepare("INSERT INTO inscription (idEt, idCours, DateInscri) VALUES (:idEt, :idCours, :DateInscri)");
                $stmt->execute(['idEt' => $idEt, 'idCours' => $idCours, 'DateInscri' => $DateInscri]);
                $_SESSION['message'] = "Student added to the course successfully.";
                $_SESSION['messageType'] = "success";
            } else {
                $_SESSION['message'] = "Student is already enrolled in this course.";
                $_SESSION['messageType'] = "error";
            }
        } elseif ($action == 'delete_student') {
            $stmt = $conn->prepare("DELETE FROM inscription WHERE idEt = :idEt");
            $stmt->execute(['idEt' => $idEt]);

            $stmt = $conn->prepare("DELETE FROM etudiant WHERE idEt = :idEt");
            $stmt->execute(['idEt' => $idEt]);

            $_SESSION['message'] = "Student deleted successfully from all courses.";
            $_SESSION['messageType'] = "success";
        } elseif ($action == 'modify_student') {
            $nom = $_POST['nom'];
            $prenom = $_POST['prenom'];
            $numParent = !empty($_POST['numParent']) ? $_POST['numParent'] : NULL;

            $stmt = $conn->prepare("UPDATE etudiant SET NomEt = :nom, PrenomEt = :prenom, numParent = :numParent WHERE idEt = :idEt");
            $stmt->execute(['nom' => $nom, 'prenom' => $prenom, 'numParent' => $numParent, 'idEt' => $idEt]);

            $_SESSION['message'] = "Student information updated successfully.";
            $_SESSION['messageType'] = "success";
        }
        header("Location: students_management.php");
        exit;
    }
}

$studentsStmt = $conn->query("SELECT * FROM etudiant ORDER BY NomEt, PrenomEt");
$students = $studentsStmt->fetchAll(PDO::FETCH_ASSOC);

$coursesStmt = $conn->query("
    SELECT co.idCours, m.NomMatiere, c.NomCategorie 
    FROM cours co 
    JOIN matiere m ON co.idMatiere = m.idMatiere 
    JOIN categorie c ON m.idCategorie = c.idCategorie
    ORDER BY c.NomCategorie, m.NomMatiere
");
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Gestion des Étudiants</title>
    <link rel="stylesheet" href="css/StudentListeStyle.css">
    <link rel="stylesheet" href="css/all.min.css">
</head>

<body>
    <a href="index.php" class="back-button"><i class="fa-solid fa-arrow-left"></i></a>
    <?php if (!empty($message)) : ?>
        <div class="message <?= $messageType ?>" style="display: block;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <br>
    <h1>Gestion des Étudiants</h1>
    <br>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Numéro Parent</th>
                    <th>Cours</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student) : ?>
                    <tr>
                        <td><?= htmlspecialchars($student['NomEt']) ?></td>
                        <td><?= htmlspecialchars($student['PrenomEt']) ?></td>
                        <td><?= htmlspecialchars($student['numParent']) ? '0' . htmlspecialchars($student['numParent']) : "" ?></td>
                        <td>
                            <?php
                            $studentCoursesStmt = $conn->prepare("
                                SELECT co.idCours, m.NomMatiere, c.NomCategorie, i.DateInscri 
                                FROM inscription i 
                                JOIN cours co ON i.idCours = co.idCours 
                                JOIN matiere m ON co.idMatiere = m.idMatiere 
                                JOIN categorie c ON m.idCategorie = c.idCategorie 
                                WHERE i.idEt = :idEt
                            ");
                            $studentCoursesStmt->execute(['idEt' => $student['idEt']]);
                            $studentCourses = $studentCoursesStmt->fetchAll(PDO::FETCH_ASSOC);

                            foreach ($studentCourses as $course) :
                            ?>
                                <?= htmlspecialchars($course['NomMatiere']) ?> (<?= htmlspecialchars($course['NomCategorie']) ?>) - <?= htmlspecialchars($course['DateInscri']) ?><br>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <button class="add-course-button" onclick="showAddToCourseForm('<?= $student['idEt'] ?>', '<?= htmlspecialchars($student['NomEt'] . ' ' . $student['PrenomEt']) ?>')"><i class="fa-solid fa-plus"></i></button>
                            <button onclick="showModifyForm('<?= $student['idEt'] ?>', '<?= htmlspecialchars($student['NomEt']) ?>', '<?= htmlspecialchars($student['PrenomEt']) ?>', '<?= htmlspecialchars($student['numParent']) ?>')"><i class="fa-solid fa-wrench"></i></button>
                            <button class="delete-button" onclick="confirmDeletion('<?= $student['idEt'] ?>', '<?= htmlspecialchars($student['NomEt'] . ' ' . $student['PrenomEt']) ?>')"><i class="fa-solid fa-user-slash"></i></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="addToCourseForm" class="modal">
        <div class="modal-content">
            <h2>Ajouter l'Étudiant à un Cours</h2>
            <form action="students_management.php" method="POST">
                <input type="hidden" name="action" value="add_to_course">
                <input type="hidden" name="idEt" id="addToCourseIdEt">
                <p id="studentNameDisplay"></p>
                <label for="idCours">Sélectionner le Cours :</label>
                <select name="idCours" id="idCours" required>
                    <?php foreach ($courses as $course) : ?>
                        <option value="<?= $course['idCours'] ?>"><?= htmlspecialchars($course['NomCategorie'] . ' - ' . $course['NomMatiere']) ?></option>
                    <?php endforeach; ?>
                </select><br><br>
                <label for="DateInscri">Date d'Inscription :</label>
                <input type="date" name="DateInscri" id="DateInscri"><br><br>
                <button type="submit" class="add-course-button">Ajouter au Cours</button>
            </form>
            <button onclick="hideAddToCourseForm()" class="cancel-button">Annuler</button>
        </div>
    </div>

    <div id="modifyForm" class="modal">
        <div class="modal-content">
            <h2>Modifier les Informations de l'Étudiant</h2>
            <form action="students_management.php" method="POST">
                <input type="hidden" name="action" value="modify_student">
                <input type="hidden" name="idEt" id="modifyIdEt">
                <label for="nom">Nom :</label>
                <input type="text" name="nom" id="modifyNom" required><br><br>
                <label for="prenom">Prénom :</label>
                <input type="text" name="prenom" id="modifyPrenom" required><br><br>
                <label for="numParent">Numéro Parent :</label>
                <input type="text" name="numParent" id="modifyNumParent"><br><br>
                <button type="submit">modifier</button>
            </form>
            <button onclick="hideModifyForm()" class="cancel-button">Annuler</button>
        </div>
    </div>

    <div id="deleteForm" class="modal">
        <div class="modal-content">
            <h2>Confirmer la Suppression</h2>
            <p id="deleteStudentName"></p>
            <form action="students_management.php" method="POST">
                <input type="hidden" name="action" value="delete_student">
                <input type="hidden" name="idEt" id="deleteIdEt">
                <button type="submit" class="delete-button">Supprimer</button>
            </form>
            <button onclick="hideDeleteForm()" class="cancel-button">Annuler</button>
        </div>
    </div>

    <script>
        function showAddToCourseForm(idEt, studentName) {
            document.getElementById('addToCourseIdEt').value = idEt;
            document.getElementById('studentNameDisplay').innerText = studentName;
            document.getElementById('addToCourseForm').style.display = 'block';
        }

        function hideAddToCourseForm() {
            document.getElementById('addToCourseForm').style.display = 'none';
        }

        function showModifyForm(idEt, nom, prenom, numParent) {
            document.getElementById('modifyIdEt').value = idEt;
            document.getElementById('modifyNom').value = nom;
            document.getElementById('modifyPrenom').value = prenom;
            document.getElementById('modifyNumParent').value = numParent;
            document.getElementById('modifyForm').style.display = 'block';
        }

        function hideModifyForm() {
            document.getElementById('modifyForm').style.display = 'none';
        }

        function confirmDeletion(idEt, studentName) {
            document.getElementById('deleteIdEt').value = idEt;
            document.getElementById('deleteStudentName').innerText = `Êtes-vous sûr de vouloir supprimer l'étudiant : ${studentName} ?`;
            document.getElementById('deleteForm').style.display = 'block';
        }

        function hideDeleteForm() {
            document.getElementById('deleteForm').style.display = 'none';
        }

        window.onload = function() {
            const message = document.querySelector('.message');
            if (message) {
                message.style.display = 'block';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 3000);
            }
        };
    </script>
</body>

</html>