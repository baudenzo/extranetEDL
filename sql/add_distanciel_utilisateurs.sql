-- Add distanciel column to utilisateurs (0 = présentiel, 1 = distanciel)
-- NOTE: some MySQL versions (or phpMyAdmin targets) do not support
-- 'ADD COLUMN IF NOT EXISTS'. If your server rejects that syntax,
-- run the check below and then the ALTER statement.

-- 1) Vérifier si la colonne existe déjà (remplacez 'edl' par le nom de la base si nécessaire)
SELECT COUNT(*) AS col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_schema = DATABASE()
	AND table_name = 'utilisateurs'
	AND column_name = 'distanciel';

-- 2) Si le résultat est 0, exécuter la commande ALTER suivante:
ALTER TABLE utilisateurs
ADD COLUMN distanciel TINYINT(1) NOT NULL DEFAULT 0;

-- (Remarque : exécutez d'abord la requête SELECT ci‑dessus dans phpMyAdmin, 
-- si elle retourne 0 lancez ensuite l'ALTER. Sauvegardez la base avant toute modification.)
