<?php session_start();
include_once("bdd.php");

if(isset($_GET['suppProd'])){
    $bdd->exec("delete from LignesCommandes where idCommande=".$_GET['idCommande']." and idProduit=".$_GET['suppProd']);
    if(!($bdd->query("select * from LignesCommandes where idCommande=".$_GET['idCommande'])->fetch())){
        $bdd->exec("delete from Commandes where idCommande=".$_GET['idCommande']);
    }
}
else if(isset($_GET['annulerCommande'])){
    $request = $bdd->query("select * from Commandes, LignesCommandes, Produits where Commandes.idCommande = LignesCommandes.idCommande and LignesCommandes.idProduit = Produits.idProduit and Commandes.idCommande=".$_GET['annulerCommande']);
    while($donnees = $request->fetch()){
        $bdd->exec("update Produits set stock=".($donnees['stock'] + $donnees['quantite'])." where idProduit=".$donnees['idProduit']);
    }
    $bdd->exec("delete from LignesCommandes where idCommande=".$_GET['annulerCommande']);
    $bdd->exec("delete from Commandes where idCommande=".$_GET['annulerCommande']);
    header("location: panier.php?commandesEnCours");
}

?>
<!doctype html>
<html lang="fr">
<head>
    <?php include_once("head/head.php"); ?>
    <title>Panier</title>
</head>
<body>
<?php include_once("nav/nav.php"); ?>

<h2>Panier</h2>
<?php
    if(!isset($_SESSION['email'])){
        echo "<center style='margin-top: 50px'><h4>Veuillez vous connecter afin d'ajouter du contenu dans votre panier<br><a href='compte.php'>Me connecter</a></h4></center>";
    }
    else if(isset($_GET['validerPanier'])){
        $request = $bdd->query("select * from Clients, Produits, Commandes, LignesCommandes where Produits.idProduit = LignesCommandes.idProduit and Commandes.idCommande = LignesCommandes.idCommande and Clients.email = Commandes.email and Commandes.email='".$_SESSION['email']."' and etat='Panier'");
        $prbStock = false;
        while($donnees = $request->fetch()){
            if($donnees['stock'] < $donnees['quantite']){
                $prbStock = true;
            }
        }
        if($prbStock){
            header("location: panier.php?error=stock");
        }
        else if(isset($_POST['ville'])){
            if(empty($_POST['complement'])) {
                $adresse = $_POST['numRue'] . " " . ucwords($_POST['rue']) . " " . ucwords($_POST['ville']) . " " . $_POST['CP'];
            }
            else{
                $adresse = $_POST['numRue'] . " " . ucwords($_POST['complement']) . " " . ucwords($_POST['rue']) . " " . ucwords($_POST['ville']) . " " . $_POST['CP'];
            }
            $bdd->exec("update Clients set ville='".ucwords($_POST['ville'])."' where email='".$_SESSION['email']."'");
            $bdd->exec("update Clients set adresse='".$adresse."' where email='".$_SESSION['email']."'");
            $bdd->exec("update Clients set telephone=".$_POST['tel']." where email='".$_SESSION['email']."'");
            header("location: panier.php?validerPanier");
        }
        $request = $bdd->query("select * from Clients, Commandes where Clients.email = Commandes.email and Clients.email='".$_SESSION['email']."' and etat='Panier'");
        $donnees = $request->fetch();
        if(empty($donnees['ville'])){
            ?>
            <h4 style="margin-top: 20px">Nous avons besoin de quelques informations supplémentaires :</h4><br>
            <form action="panier.php?validerPanier" method="POST">
                <input type="number" name="numRue" placeholder="N° rue" class="panier" required>
                <input type="text" name="complement" placeholder="Bis, Ter, ..." class="panier">
                <input type="text" name="rue" placeholder="Rue" class="panier" required>
                <input type="text" name="ville" placeholder="Ville" class="panier" required>
                <input type="number" name="CP" placeholder="Code Postal" class="panier" required>
                <br><br>
                <input type="tel" name="tel" placeholder="Tel" class="panier" required><br><br>
                <input type="submit" class="panier">
            </form>
            <?php
        }
        else{
            $request = $bdd->query("select SUM(montant) as total, SUM(quantite) as nbProduit from Commandes, LignesCommandes where Commandes.idCommande = LignesCommandes.idCommande and Commandes.email='".$_SESSION['email']."' and etat='Panier'")->fetch();
            $total = $request['total'];
            $nbProduit = $request['nbProduit'];
            $promotion = "";

            if(!empty($_POST['promotion'])){
                if($_POST['promotion'] == "université"){
                    $total = 0;
                    $promotion .= "<h4>Votre code de promotion \"université\" a été appliqué !<br><a href='panier.php?finaliserPanier'>Finaliser ma commande</a></h4>";
                }
                else{
                    $promotion .= "<h4>Ce code de promotion n'existe pas</h4>";
                }
            }

            $donnees = $bdd->query("select * from Clients, Commandes where Clients.email = Commandes.email and Commandes.email='".$_SESSION['email']."' and etat='Panier'")->fetch();

            ?>
            <div style="height: 30px"></div>
            <div>
                <div id="paypal-button-container" class="right" style="width: 30%; z-index: 1;"></div>
                <h4>Montant : <span id="total"><?= $total ?></span> EUR</h4>
                <p>
                    Nombre d'articles : <?= $nbProduit ?><br>
                    Frais de livraison : 0.00€<br>
                    Adresse : <?= $donnees['adresse'] ?>
                </p>
                <form action="panier.php?validerPanier" method="POST">
                    <label style="margin-top: 10px">Code de promotion : <input type="text" name="promotion"></label>
                    <input type="submit" value="Appliquer" class="button">
                </form>

                <script src="https://www.paypal.com/sdk/js?client-id=AWz2FJiKkeO3v1cSy7cAcG7-jMjWfNb0zrxsfSZU8p_8_pwUhEwlRWUOS690wXJzI8c4y0jsXv5vFtwX&currency=EUR"></script>
                <script src="JavaScript/paypal.js"></script>
                <div style="height: 30px"></div>
                <center><?= $promotion ?></center>
                <table>
                    <tr class="bold"><td>Produit</td><td style="color:white">blabla</td><td>Quantité</td><td style="color:white">blabla</td><td>Prix</td><td style="color:white">blabla</td><td>Promotion</td></tr>
                    <?php
                    $request = $bdd->query("select * from Commandes, LignesCommandes, Produits where Commandes.idCommande = LignesCommandes.idCommande and LignesCommandes.idProduit = Produits.idProduit and email='".$_SESSION['email']."' and etat='Panier'");
                    while($donnees2 = $request->fetch()){
                        ?>
                        <tr><td><?= $donnees2['nom'] ?></td><td></td><td class="centre"><?= $donnees2['quantite'] ?></td><td></td><td class="centre"><?= $donnees2['montant'] ?>€</td><td></td><td class="centre"><?php if($donnees2['promotion'] != 0){ echo "-".$donnees2['promotion']."%";}else{ echo "/";} ?></td></tr>
                        <?php
                    }
                    ?>
                </table>
            </div>
            <?php
        }
    }
    else if(isset($_GET['finaliserPanier'])){
        $request = $bdd->query("select * from Commandes, LignesCommandes, Produits where Commandes.idCommande = LignesCommandes.idCommande and LignesCommandes.idProduit = Produits.idProduit and email='".$_SESSION['email']."' and etat='Panier';");
        while($donnees = $request->fetch()){
            $bdd->exec("update Produits set stock=".($donnees['stock'] - $donnees['quantite'])." where idProduit=".$donnees['idProduit']);
        }
        $bdd->exec("update Commandes set date_ = now() where email='".$_SESSION['email']."' and etat='Panier'");
        $bdd->exec("update Commandes set etat='Validee' where email='".$_SESSION['email']."' and etat='Panier'");
        ?>
        <h4 class="good colle" style="margin-top: 30px">Envoyé !</h4>
        <h4>Votre commande vient d'être envoyé à notre équipe ! <br>Vous pouvez voir son status dans la rubrique <span class="italic">Commandes en cours</span></h4>
        <?php
    }
    else if(isset($_GET['commandesEnCours'])){
        $donnees = $bdd->query("select COUNT(*) as  nbCommande from Commandes where email='".$_SESSION['email']."' and etat='Validee'")->fetch();
        if($donnees['nbCommande']){
            $request1 = $bdd->query("select * from Commandes where email='".$_SESSION['email']."' and etat='Validee'");
            $compteur = 1;
            while($donnees1 = $request1->fetch()){
                $donneesSupp = $bdd->query("select SUM(quantite) as nbArticle, SUM(montant) as total from Commandes, LignesCommandes where Commandes.idCommande = LignesCommandes.idCommande and Commandes.idCommande=".$donnees1['idCommande'])->fetch();
                ?>
                <div class="row contentColor">
                    <div class="col-sm-4 centre">
                        <h5>Commandé le : <?= date('d M Y', strtotime($donnees1['date_'])) ?><br>
                        Statut : En cours de préparation</h5>
                    </div>
                    <div class="col-sm-3 centre">
                        <h5>Nombre d'articles : <?= $donneesSupp['nbArticle'] ?><br>
                            Total : <?= $donneesSupp['total'] ?> €</h5>
                    </div>
                    <div class="col-sm-2 centre">
                        <a href="#" class="lien" onclick="afficheListeArticle(<?= $compteur ?>)">Voir les articles</a>
                    </div>
                    <div class="col-sm-3 centre">
                        <a <?= "href='panier.php?&annulerCommande=".$donnees1['idCommande']."'" ?> class="lien">Annuler la commande</a>
                    </div>
                </div>
                <div <?= "id='article".$compteur."'" ?> style="display: none" class="contentColorArticle">
                <?php
                $request2 = $bdd->query("select * from Commandes, LignesCommandes, Produits where Commandes.idCommande = LignesCommandes.idCommande and LignesCommandes.idProduit = Produits.idProduit and Commandes.idCommande=".$donnees1['idCommande']);
                while($donnees2 = $request2->fetch()){
                    ?>
                        <div  class="row">
                            <div class="col-sm-2 centre">
                                <div class="bgWhite">
                                    <img <?= "src=../donnees/img/".$donnees2['photo']; ?> style="height: 50px"/>
                                </div>
                            </div>
                            <div class="col-sm-2 centre paddingTop">
                                <h5><?= $donnees2['nom']; ?></h5>
                            </div>
                            <div class="col-sm-3 centre paddingTop">
                                <h5>Quantité : <?= $donnees2['quantite'] ?></h5>
                            </div>
                            <div class="col-sm-3 centre paddingTop">
                                <h5>Prix à l'unité : <?= $donnees2['prix'] ?>€
                                <?php
                                if($donnees2['promotion'] != 0)
                                    echo "<br>Promotion : ".$donnees2['promotion']."%";
                                ?>
                                </h5>
                            </div>
                            <div class="col-sm-2 centre paddingTop">
                                <a <?= "href='article.php?article=".$donnees2['idProduit']."'" ?> style="font-size: 18px"><p>Plus d'informations</p></a>
                            </div>
                        </div>
                    <p></p>
                    <?php
                }
                echo "</div>";
                $compteur++;
                echo "<hr>";
            }
            echo "<script src='JavaScript/commandeArticle.js'></script>";
        }
        else{
            echo "<center style='margin-top: 50px'><h4>Vous n'avez aucune commande en cours</h4></center>";
        }
    }
    else{
        ?>
        <a href="panier.php?commandesEnCours" class="button right" style="font-size: 18px; margin-top: -30px">Commandes en cours</a>
        <h3 class="underline" style="margin-top: 30px">Votre panier actuel</h3>
        <?php
        $donnees = $bdd->query("select COUNT(*) as panier from Commandes where email='".$_SESSION['email']."' and etat='Panier'")->fetch();
        if($donnees['panier'])
            echo "<a href='panier.php?validerPanier' class='button right' style='font-size: 18px; margin-top: 30px'>Valider mon Panier</a>";

        if($id = $bdd->query("select idCommande, email, etat from Commandes where email='".$_SESSION['email']."' and etat='Panier'")->fetch()){
            $total = $bdd->query("select SUM(montant) as total from LignesCommandes, Produits where LignesCommandes.idProduit = Produits.idProduit and idCommande=".$id['idCommande'])->fetch();
            echo "<h3 style='margin-top: 30px'>Montant total du panier : ".$total['total']."€</h3>";
            $request = $bdd->query("select * from LignesCommandes, Produits where LignesCommandes.idProduit = Produits.idProduit and idCommande=".$id['idCommande']);
            while($donnees = $request->fetch()){
                ?>
                <a <?= "href='article.php?article=".$donnees['idProduit']."'" ?> class="contenu">
                    <div class="article row">
                        <div class="col-sm-3 centre">
                            <h5><?= $donnees['nom']; ?></h5>
                            <img <?= "src=../donnees/img/".$donnees['photo']; ?> class="recherche"/>
                        </div>
                        <div class="col-sm-3 prix centre">
                            <?php if($donnees['promotion'] != 0){
                                ?>
                                <p>Prix à l'unité : <span class="texteBarre"><?= $donnees['prix']; ?></span>€<br>
                                Promotion : <span style="font-weight:bold"><?= $donnees['prix']*(100 - $donnees['promotion'])/100; ?>€</span></p>
                                <?php
                            }else{
                                ?>
                                <p>Prix à l'unité : <?= $donnees['prix'] ?>€</p>
                                <?php
                            } ?>
                        </div>
                        <div class="col-sm-3 centre prix">
                            <p>Quantité : <?= $donnees['quantite'] ?>
                                <?php
                                $stock = $bdd->query("select stock from Produits where idProduit=".$donnees['idProduit'])->fetch();
                                    if($stock['stock'] <  $donnees['quantite']){
                                        echo "<br><span style='color: red'>Problème de stock !</span>";
                                    }
                                ?>
                            </p>
                        </div>
                        <div class="col-sm-3 centre prix">
                            <p>
                                <?php $url = "panier.php?suppProd=".$donnees['idProduit']."&idCommande=".$id['idCommande']; ?>
                                <a href=<?= $url ?>>Supprimer du panier</a>
                            </p>
                        </div>
                    </div>
                </a>
                <hr>
                <?php
            }
        }
        else{
            echo "<center style='margin-top: 50px'><h4>Vous n'avez actuellement rien dans votre panier</h4></center>";
        }
    }
    $bdd = null;
?>

</body>
</html>