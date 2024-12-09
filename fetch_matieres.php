<?php
include('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $categorie = $_POST['categorie'];
    $stmt = $conn->prepare("SELECT * FROM matiere WHERE idCategorie = :categorie");
    $stmt->bindParam(':categorie', $categorie);
    $stmt->execute();
    $matieres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($matieres);
}
?>
