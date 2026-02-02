-- Ajout sécurisé des clés étrangères pour la table stagiaire_formateur
-- Exécuter seulement si la table `utilisateurs` existe et utilise InnoDB.

ALTER TABLE `stagiaire_formateur`
  ADD CONSTRAINT `fk_sf_stagiaire` FOREIGN KEY (`stagiaire_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_sf_formateur` FOREIGN KEY (`formateur_id`) REFERENCES `utilisateurs`(`id`) ON DELETE CASCADE;
