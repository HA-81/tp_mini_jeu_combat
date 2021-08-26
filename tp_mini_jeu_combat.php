<?php
class Personnage
{
	protected $atout,
			  $degats,
			  $id,
			  $nom,
			  $timeEndormi,
			  $type;

	const CEST_MOI = 1;
	const PERSONNAGE_TUE = 2;
	const PERSONNAGE_FRAPPE = 3;
	const PERSONNAGE_ENSORCELE =4;
	const PAS_DE_MAGIE = 5;
	const PERSO_ENDORMI = 6;

	public function __construct(array $donnees)
  	{
  		$this->hydrate($donnees);
  		$this->type = strtolower(static::class);
  	}

  	public function estEndormi()
  	{
  		return $this->timeEndormi > time();
  	}

  	public function frapper(Personnage $perso)
  	{
  		if ($perso-> id == $this->id)
  		{
  			return self::CEST_MOI;
  		}
  		if ($this->estEndormi())
  		{
  			return self::PERSO_ENDORMI;
  		}
  	
		return $perso->recevoirDegats();
	}

	public function hydrate(array $donnees)
    {
    	foreach ($donnees as $key => $value)
    	{
        	$method = 'set'.ucfirst($key);

        	if (method_exists($this, $method))
        	{
        		$this->$method($value);
      		}
    	}
  	}

  	public function nomValide()
  	{
  		return !empty($this->_nom);
  	}

	public function recevoirDegats()
	{
		$this->_degats += 5;

		if ($this->_degats >= 100)
		{
			return self::PERSONNAGE_TUE;
		}
		return self::PERSONNAGE_FRAPPE;
	}

	public function reveil()
	{
		$secondes = $this->timeEndormi;
    	$secondes -= time();
    
    	$heures = floor($secondes / 3600);
    	$secondes -= $heures * 3600;
    	$minutes = floor($secondes / 60);
    	$secondes -= $minutes * 60;
    
    	$heures .= $heures <= 1 ? ' heure' : ' heures';
    	$minutes .= $minutes <= 1 ? ' minute' : ' minutes';
    	$secondes .= $secondes <= 1 ? ' seconde' : ' secondes';
    
    	return $heures . ', ' . $minutes . ' et ' . $secondes;
	}

	public function atout()
	{
		return $this->atout;
	}

	public function degats()
	{
		return $this->degats;
	}

	public function id()
	{
		return $this->id;
	}

	public function nom()
	{
		return $this->nom;
	}

	public function timeEndormi()
	{
		return $this->timeEndormi;
	}

	public function type()
	{
		return $this->type;
	}

	public function setAtout($atout)
	{
		$atout = (int) $atout;

		if ($atout >= 0 && $atout <= 100) 
		{
			$this->atout = $atout;
		}
	}

	public function setDegats($degats)
	{
		$degats = (int) $degats;

		if ($degats >= 0 && $degats <= 100) 
		{
			$this->degats = $degats;
		}
	}

	public function setId($id)
	{
		$id = (int) $id;
		if ($id > 0 ) 
		{
			$this->id = $id;
		}
	}
	
	public function setNom($nom)
	{
		if (is_string($nom))
		{
			$this->nom = $nom;
		}
	}

	public function setTimeEndormi($time)
	{
		$this->timeEndormi = (int) $time;
	}
}

class Guerrier extends Personnage
{
	public function recevoirDegats()
	{
		if ($this->degats >= 0 && $this->degats <= 25) 
		{
			$this->atout = 4;
		}
		elseif ($this->degats > 25 && $this->degats <= 50) 
		{
			$this->atout = 3;
		}
		elseif ($this->degats > 50 && $this->degats <= 75)
		{
			$this->atout = 2;
		}
		elseif ($this->degats > 75 && $this->degats <= 90)
		{
			$this->atout = 1;
		}
		else
		{
			$this->atout = 0;
		}

		$this->degats += 5 - $this->atout;

		if ($this->degats >= 100)
		{
			return self::PERSONNAGE_TUE;
		}

		return self::PERSONNAGE_FRAPPE;
	}
}

class Magicien extends Personnage
{
	public function lancerUnSort(Personnage $perso)
	{
		if ($this->degats >= 0 && $this->degats <= 25)
		{
			$this->atout = 4;
		}
		elseif ($this->degats > 25 && $this->degats <= 50)
	    {
	      $this->atout = 3;
	    }
	    elseif ($this->degats > 50 && $this->degats <= 75)
	    {
	      $this->atout = 2;
	    }
	    elseif ($this->degats > 75 && $this->degats <= 90)
	    {
	      $this->atout = 1;
	    }
	    else
	    {
	      $this->atout = 0;
	    }
	    
	    if ($perso->id == $this->id)
	    {
	      return self::CEST_MOI;
	    }
	    
	    if ($this->atout == 0)
	    {
	      return self::PAS_DE_MAGIE;
	    }
	    
	    if ($this->estEndormi())
	    {
	      return self::PERSO_ENDORMI;
	    }
	    
	    $perso->timeEndormi = time() + ($this->atout * 6) * 3600;
	    
	    return self::PERSONNAGE_ENSORCELE;
	}
}

class PersonnagesManager
{
	private $db; // Instance de PDO
  
	public function __construct($db)
	{
		$this->db = $db;
	}

	public function add(Personnage $perso)
	{
		$q = $this->_db->prepare('INSERT INTO personnages(nom, type) VALUES(:nom, :type)');
		$q->bindValue(':nom', $perso->nom());
		$q->bindValue(':type', $perso->type());

		$q->execute();

		$perso->hydrate([
			'id'=> $this->db->lastInsertId(),
			'degats' => 0,
			'atout' => 0
		]);
		
	}

	public function count()
	{
		return $this->db->query('SELECT COUNT(*) FROM personnages')->fetchColumn();
	}

	public function delete(Personnage $perso)
	{
		$this->db->exec('DELETE FROM personnages WHERE id = '.$perso->id());
	}

	public function exists($info)
	{
		if (is_int($info))
		{
			return (bool) $this->_db->query('SELECT COUNT(*) FROM personnages WHERE id = '.$info)->fetchColumn();
		}

		$q = $this->db->prepare('SELECT COUNT(*) FROM personnages WHERE nom = :nom');
		$q->execute([':nom' => $info]);

		return (bool) $q->fetchColumn();
	}

	public function get($info)
	{
		if (is_int($info))
		{
			$q = $this->_db->query('SELECT id, nom, degats, timeEndormi, type, atout FROM personnages WHERE id = '.$info);
			$perso = $q->fetch(PDO::FETCH_ASSOC);
		}

		else
		{
			$q = $this->db->prepare('SELECT id, nom, degats, timeEndormi, type, atout FROM personnages WHERE nom = :nom');
			$q->execute([':nom' => $info]);

			$perso = $q->fetch(PDO::FETCH_ASSOC);
		}

		switch ($perso['type'])
	    {
	      case 'guerrier': return new Guerrier($perso);
	      case 'magicien': return new Magicien($perso);
	      default: return null;
	    }
	}

	public function getList($nom)
	{
		$persos = [];

		$q = $this->db->prepare('SELECT id, nom, degats, timeEndormi, type,atout FROM personnages WHERE nom <> :nom ORDER BY nom');
		$q->execute([':nom' => $nom]);

		while ($donnees = $q->fetch(PDO::FETCH_ASSOC))
		{
			switch ($donnees['type'])
			{
		        case 'guerrier': $persos[] = new Guerrier($donnees); break;
		        case 'magicien': $persos[] = new Magicien($donnees); break;
	        }
		}

		return $persos;
	}

	public function update(Personnage $perso)
	{
		$q = $this->db->prepare('UPDATE personnages SET degats = :degats, timeEndormi = :timeEndormi, atout = :atout WHERE id = :id');
    
    	$q->bindValue(':degats', $perso->degats(), PDO::PARAM_INT);
    	$q->bindValue(':timeEndormi', $perso->timeEndormi(), PDO::PARAM_INT);
    	$q->bindValue(':atout', $perso->atout(), PDO::PARAM_INT);
    	$q->bindValue(':id', $perso->id(), PDO::PARAM_INT);
    
    	$q->execute();
	}
}

function chargerClasse($classe)
{
	require $classe .'.php';
}

spl_autoload_register('chargerClasse');

session_start();

if (isset($_GET['deconnexion']))
{
	session_destroy();
	header('Location: .');
	exit();
}

$db = new PDO('mysql:host=localhost;dbname=test-poo', 'root', 'root');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

$manager = new PersonnagesManager($db);

if (isset($_SESSION['perso']))
{
	$perso = $_SESSION['perso'];
}

if (isset($_POST['creer']) && isset($_POST['nom']))
{
	switch ($_POST['type']) 
	{
		case 'magicien' :
			$perso = new Magicien(['nom' => $_POST['nom']]);
			break;

		case 'guerrier' :
			$perso = new Guerrier(['nom' => $_POST['nom']]);
			break;
		
		default:
			$message = 'Le type dupersonnage est invalide.';
			break;
	}

 	if(isset($perso))
 	{
		if (!$perso->nomValide())
		{
			$message = 'Le nom choisi est invalide.';
			unset($perso);
		}
		elseif ($manager->exists($perso->nom()))
		{
			$message = 'Le nom du personnage est déjà pris.';
			unset($perso);
		}
		else
		{
			$manager->add($perso);
		}
	}
}

elseif (isset($_POST['utiliser']) && isset($_POST['nom']))
{
	if ($manager->exists($_POST['nom']))
	{
		$perso = $manager->get($_POST['nom']);
	}
	else
	{
		$message = 'Ce personnage n\'existe pas !';
	}
}

elseif (isset($_GET['frapper'])) // Si on a cliqué sur un personnage pour le frapper.
{
    if (!isset($perso))
    {
        $message = 'Merci de créer un personnage ou de vous identifier.';
    }
  
    else
    {
        if (!$manager->exists((int) $_GET['frapper']))
    {
      $message = 'Le personnage que vous voulez frapper n\'existe pas !';
    }
    
    else
    {
      $persoAFrapper = $manager->get((int) $_GET['frapper']);
      
      $retour = $perso->frapper($persoAFrapper); // On stocke dans $retour les éventuelles erreurs ou messages que renvoie la méthode frapper.
      
        switch ($retour)
        {
        case Personnage::CEST_MOI :
          $message = 'Mais... pourquoi voulez-vous vous frapper ???';
        break;
        
        case Personnage::PERSONNAGE_FRAPPE :
          $message = 'Le personnage a bien été frappé !';
          
          $manager->update($perso);
          $manager->update($persoAFrapper);
          
        break;
        
        case Personnage::PERSONNAGE_TUE :
        $message = 'Vous avez tué ce personnage !';
          
        $manager->update($perso);
        $manager->delete($persoAFrapper);
          
        break;

        case Personnage::PERSO_ENDORMI :
        	$message = 'Vous êtes endormi, vous ne pouvez pas frapper de personnage !';
        	break;
        }
    }
  }
}

elseif (isset($_GET['ensorceler']))
{
	if (!isset($perso))
	{
		$message = 'Merci de créer un personnage ou de vous identifier.';
	}

	else 
	{
		if ($perso->type() != 'magicien')
		{
			$message = 'Seuls les magiciens peuvent ensorceler des personnages !';
		}

		else 
		{
			if (!$message->exicts((int) $_GET['ensorceler']))
			{
				$message = 'Le personnage que vous voulez frapper n\'existe pas !';
			}

			else 
			{
				$persoAEnsorceler = $manager->get((int) $_GET['ensorceler']);
				$retour = $perso->lancerUnSort($persoAEnsorceler);

				switch ($retour) 
				{
					case Personnage::CEST_MOI :
						$message = 'Mais... pourquoi voulez-vous vous ensorceler ???';
						break;

					case Personnage::PERSONNAGE_ENSORCELE :
						$message = 'Le personnage a bien été ensorcelé !';

						$manager->update($perso);
						$manager->update($persoAEnsorceler);

						break;

					case Personnage::PAS_DE_MAGIE :
						$message = 'Vous n\'avez pas de magie !';
						break;

					case Personnage::PERSO_ENDORMI :
						$message = 'Vous êtes endormi, vous ne pouvez pas lancer de sort !';
						break;
				}
			}
		}
	}
}

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>TP : Mini jeu de combat</title>
</head>
<body>
	<p>Nombre de personnages crées : <?= $manager->count() ?></p>
	<?php
	if (isset($message))
	{
		echo '<p>', $message, '</p>';
	}

	if (isset($perso))
	{
	?>
	<p><a href="?deconnexion=1">Déconnexion</a></p>
		<fieldset>
			<legend>Mes informations</legend>
			<p>
				Type : <?= ucfirst($perso->type()) ?><br />
				Nom : <?= htmlspecialchars($perso->nom()) ?><br />
				Dégâts : <?= $perso->degats() ?><br />
	<?php

	switch ($perso->type()) 
	{
		case 'magicien' :
			echo 'Magie : ';
			break;
		
		case 'guerrier' :
			echo 'Protection : ';
			break;
	}

	echo $perso->atout();
	?>
			</p>
		</fieldset>

		<fieldset>
			<legend>Qui attaquer ?</legend>
			<p>
	<?php

	$retourPersos = $manager->getList($perso->nom());

	if (empty($retourPersos))
	{
		echo 'Personne à frapper !';
	}

	else
	{
		if ($perso->estEndormi())
		{
			echo 'Un magicien vous a endormi ! Vous allez vous réveiller dans ', $perso->reveil(), '.';
		}

		else
		{
			foreach ($retourPersos as $sunPerso)
			{
				echo '<a href="?frapper=', $unPerso->id(), '">', htmlspecialchars($unPerso->nom()), '</a> (dégats : ', $unPerso->degats(), ' | type : ', $unPerso->type(), ')';
				if ($perso->type() == 'magicien')
				{
					echo ' | <a href="?ensorceler=', $unPerso->id(), '">Lancer un sort</a>';
				}
				echo '<br />';
			}
		}
	}
	?>
			</p>
		</fieldset>
	<?php	
	}
	else
	{
	?>
		<form action="" method="post">
			<p>
				Nom : <input type="text" name="nom" maxlength="50" />
				<input type="submit" value="Utiliser ce personnage" name="utiliser" /><br />
				Type :
				<select name="type">
					<option value="magicien">Magicien</option>
					<option value="guerrier">Guerrier</option>
				</select>
				<input type="submit" value="Créer ce personnage" name="creer" />
			</p>
		</form>
	<?php
}
?>
</body>
</html>
<?php
if (isset($perso))
{
	$_SESSION['perso'] = $perso;
}
?>