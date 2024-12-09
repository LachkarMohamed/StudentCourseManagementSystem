<?php include('db.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="CSS/styles.css">
    <link rel="stylesheet" href="css/all.min.css">

</head>

<body>
    <header>
        <div class="logo">
            <img src="img/logo.jpg" alt="Logo">
            <h1>CENTRE KALIMAT WA HOROF</h1>
        </div>
    </header>

    <main>
        <div class="links">
            <div class="link-item">
                <a href="register.php">
                    <img src="img/addEtLOGO.png" alt="Enregistrer un etudiant" class="circle-image">
                    <p>Enregistrer un etudiant</p>
                </a>
            </div>
            <div class="link-item">
                <a href="view_delete.php">
                    <img src="img/matiereListe.jpg" alt="Liste des matieres" class="circle-image">
                    <p>Liste des matieres</p>
                </a>
            </div>
            <div class="link-item">
                <a href="students_management.php">
                    <img src="img/Etliste.png" alt="Liste des etudiants" class="circle-image">
                    <p>Liste des etudiants</p>
                </a>
            </div>
            <div class="link-item">
                <a href="professors_management.php">
                    <img src="img/prof.png" alt="Liste des matieres" class="circle-image">
                    <p>Liste des prof</p>
                </a>
            </div>
        </div>
    </main>
</body>

</html>