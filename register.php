<?php
include('db.php');

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $age = !empty($_POST['age']) ? $_POST['age'] : NULL;
    $cine = !empty($_POST['cine']) ? $_POST['cine'] : NULL;
    $numParent = !empty($_POST['numParent']) ? $_POST['numParent'] : NULL;
    $dateInscri = !empty($_POST['DateInscri']) ? $_POST['DateInscri'] : NULL;
    $categories = $_POST['categorie'] ?? [];
    $matieres = $_POST['matiere'] ?? [];

    try {
        if (empty($nom) || empty($prenom)) {
            throw new Exception("Le nom et le prénom sont obligatoires.");
        }

        // Vérifier si la combinaison NomEt et PrenomEt est unique
        $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiant WHERE NomEt = :nom AND PrenomEt = :prenom");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            throw new Exception("Un étudiant avec ce nom existe déjà.");
        }

        // Vérifier si le CIN est unique si fourni
        if (!is_null($cine)) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM etudiant WHERE CINEt = :cine");
            $stmt->bindParam(':cine', $cine);
            $stmt->execute();
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                throw new Exception("CIN existe déjà.");
            }
        }

        // Insérer dans la table etudiant
        $stmt = $conn->prepare("INSERT INTO etudiant (NomEt, PrenomEt, age, CINEt, numParent) VALUES (:nom, :prenom, :age, :cine, :numParent)");
        $stmt->bindParam(':nom', $nom);
        $stmt->bindParam(':prenom', $prenom);
        $stmt->bindParam(':age', $age, PDO::PARAM_INT);
        $stmt->bindParam(':cine', $cine, PDO::PARAM_STR);
        $stmt->bindParam(':numParent', $numParent, PDO::PARAM_INT);
        $stmt->execute();
        $etudiant_id = $conn->lastInsertId();

        // Insérer dans la table inscription pour chaque matière sélectionnée
        $stmt = $conn->prepare("SELECT idCours FROM cours WHERE idMatiere = :matiere LIMIT 1");

        foreach ($matieres as $matiere) {
            if (empty($matiere)) continue;

            $stmt->bindParam(':matiere', $matiere);
            $stmt->execute();
            $idCours = $stmt->fetchColumn();

            if (!$idCours) {
                throw new Exception("Matière sélectionnée invalide.");
            }

            $stmt_inscription = $conn->prepare("INSERT INTO inscription (idEt, idCours, DateInscri) VALUES (:etudiant_id, :idCours, :DateInscri)");
            $stmt_inscription->bindParam(':etudiant_id', $etudiant_id);
            $stmt_inscription->bindParam(':idCours', $idCours);
            $stmt_inscription->bindParam(':DateInscri', $dateInscri);
            $stmt_inscription->execute();
        }

        $message = "Étudiant enregistré avec succès.";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Erreur de base de données : " . $e->getMessage();
        $messageType = "error";
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Étudiant</title>
    <link rel="stylesheet" href="CSS/RegisterStyle.css">
    <link rel="stylesheet" href="css/all.min.css">

    <script>
        function updateMatieres(element) {
            var categorieId = element.value;
            var matiereSelect = element.closest('.category-matiere-group').querySelector('.matiere');

            // Effacer les options actuelles
            matiereSelect.innerHTML = '<option value="">Sélectionnez une Matière</option>';

            if (categorieId) {
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'fetch_matieres.php', true);
                xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status == 200) {
                        var matieres = JSON.parse(this.responseText);
                        matieres.forEach(function(matiere) {
                            var option = document.createElement('option');
                            option.value = matiere.idMatiere;
                            option.textContent = matiere.NomMatiere;
                            matiereSelect.appendChild(option);
                        });
                    }
                };
                xhr.send('categorie=' + categorieId);
            }
        }

        function addCategoryMatiereGroup() {
            var container = document.getElementById('category-matiere-container');
            var group = document.createElement('div');
            group.className = 'category-matiere-group';

            group.innerHTML = `
                <label for="categorie">Catégorie:</label>
                <select class="categorie" name="categorie[]" onchange="updateMatieres(this)">
                    <option value="">Sélectionnez une Catégorie</option>
                    <?php
                    $stmt = $conn->query("SELECT * FROM categorie");
                    while ($row = $stmt->fetch()) {
                        echo "<option value='" . $row['idCategorie'] . "'>" . $row['NomCategorie'] . "</option>";
                    }
                    ?>
                </select>
                <label for="matiere">Matière:</label>
                <select class="matiere" name="matiere[]">
                    <option value="">Sélectionnez une Matière</option>
                </select>
                <button type="button" onclick="removeCategoryMatiereGroup(this)">X</button>
            `;

            container.appendChild(group);
        }

        function removeCategoryMatiereGroup(button) {
            var group = button.closest('.category-matiere-group');
            group.remove();
        }

        function showMessage(message, isSuccess) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = message;
            messageDiv.className = 'message ' + (isSuccess ? 'success' : 'error');
            messageDiv.style.display = 'block';
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 3000);
        }

        function submitForm(event) {
            event.preventDefault();

            const formData = new FormData(event.target);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'register.php', true);
            xhr.onload = function() {
                if (this.status == 200) {
                    showMessage('Étudiant enregistré avec succès!', true);
                    event.target.reset();
                    const container = document.getElementById('category-matiere-container');
                    container.innerHTML = '';
                } else {
                    showMessage('Échec de l\'enregistrement de l\'étudiant.', false);
                }
            };
            xhr.send(formData);
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Attacher un écouteur d'événement à la soumission du formulaire
            const form = document.querySelector('form');
            form.addEventListener('submit', submitForm);
        });
    </script>
</head>

<body>
    <a href="index.php" class="back-button"><i class="fa-solid fa-arrow-left"></i></a>
    <div class="message success" id="message" style="display: none;"></div>
    <div class="form-container">
        <h1>Inscription Étudiant</h1>
        <form action="register.php" method="POST">
            <div class="form-content">
                <div class="form-section student-info">
                    <label for="nom">Nom:</label>
                    <input type="text" id="nom" name="nom" required> <br>
                    <label for="prenom">Prénom:</label>
                    <input type="text" id="prenom" name="prenom" required><br>
                    <label for="age">Âge:</label>
                    <input type="number" id="age" name="age"><br>
                    <label for="cine">CIN:</label>
                    <input type="text" id="cine" name="cine"><br>
                    <label for="numParent">Numéro du Parent:</label>
                    <input type="number" id="numParent" name="numParent"><br>
                    <label for="DateInscri">Date d'inscription:</label>
                    <input type="date" id="DateInscri" name="DateInscri"><br>
                </div>

                <div class="form-section course-info">
                    <div id="category-matiere-container"></div>
                    <button type="button" class="add-course-button" onclick="addCategoryMatiereGroup()">Ajouter un autre cours</button>
                </div>
            </div>
            <div class="form-footer">
                <button type="submit" class="register-button">Inscrire</button>
            </div>
        </form>
    </div>
</body>

</html>
