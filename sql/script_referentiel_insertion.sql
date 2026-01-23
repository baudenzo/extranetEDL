-- Script d'insertion des données du référentiel pédagogique

-- Module: Bases
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Bases', 'B-C1', 'Culture pays anglosaxons', 'A1'),
('Bases', 'B-C2', 'Salutations', 'A1'),
('Bases', 'B-C3', 'Se présenter', 'A1'),
('Bases', 'B-C4', 'Chiffres/dates/heures', 'A1'),
('Bases', 'B-C5', 'Construction de phrase', 'A1'),
('Bases', 'B-C6', 'Like/dislike', 'A1');

-- Module: Conjugaison
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Conjugaison', 'C-C1', 'BE et HAVE', 'A1'),
('Conjugaison', 'C-C2', 'Présent simple / -ING', 'A1'),
('Conjugaison', 'C-C3', 'Les temps futurs et passés', 'A1'),
('Conjugaison', 'C-C4', 'Les modaux + niveau A2/B1', 'A1'),
('Conjugaison', 'C-C5', 'Prétérit simple / -ING', 'A1,A2,B1'),
('Conjugaison', 'C-C6', 'Présent perfect simple/ -ING', 'A2,B1'),
('Conjugaison', 'C-C7', 'Les verbes irréguliers', 'A2,B1'),
('Conjugaison', 'C-C8', 'Le conditionnel', 'A2,B1'),
('Conjugaison', 'C-C9', 'Les temps complexes (futur antérieur, plus que parfait, subjonctif,...)', 'B1,B2');

-- Module: Grammaire
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Grammaire', 'G-C1', 'Les pronoms', 'A1'),
('Grammaire', 'G-C2', 'Les adverbes', 'A1'),
('Grammaire', 'G-C3', 'Mots interrogatifs', 'A1'),
('Grammaire', 'G-C4', 'Possession', 'A1'),
('Grammaire', 'G-C5', 'Articles (the, a/an)', 'A1'),
('Grammaire', 'G-C6', 'Le pluriel des noms', 'A1'),
('Grammaire', 'G-C7', 'Les adjectifs', 'A1'),
('Grammaire', 'G-C8', 'La quantité', 'A1'),
('Grammaire', 'G-C9', 'Les prépositions', 'A1'),
('Grammaire', 'G-C10', 'La fréquence', 'A2,B1'),
('Grammaire', 'G-C11', 'La comparaison', 'A2,B1'),
('Grammaire', 'G-C12', 'Les verbes à particules + niveau A2/B1', 'A2,B1'),
('Grammaire', 'G-C13', 'La voie passive', 'A2,B1'),
('Grammaire', 'G-C14', 'L\'hypothèse', 'A2,B1'),
('Grammaire', 'G-C15', 'Les pronoms relatifs (auquel, lesquels,...)', 'B1,B2'),
('Grammaire', 'G-C16', 'La mise en relief', 'C1,C2'),
('Grammaire', 'G-C17', 'Phrases verbales', NULL);

-- Module: Prononciation
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Prononciation', 'P-C1', 'Phonétique, prononciation, accents + niveau C1/C2', 'A2,B1,C1,C2'),
('Prononciation', 'P-C2', 'Intonation, débit + niveau C1/C2', 'B1,B2,C1,C2');

-- Module: Methodologie
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Methodologie', 'M-C1', 'Rédaction d\'email/lettres/messages', 'A2,B1'),
('Methodologie', 'M-C2', 'Donner son avis', 'A2,B1'),
('Methodologie', 'M-C3', 'Commenter', 'B1,B2'),
('Methodologie', 'M-C4', 'Faire un exposé, un compte rendu, commentaire', 'B1,B2'),
('Methodologie', 'M-C5', 'Rédaction CV et lettres', 'C1,C2'),
('Methodologie', 'M-C6', 'Rédiger en s\'adaptant aux différents styles', 'C1,C2'),
('Methodologie', 'M-C7', 'Rédaction de toutes sortes de documents', 'C1,C2'),
('Methodologie', 'M-C8', 'Réaliser des présentations', 'C1,C2'),
('Methodologie', 'M-C9', 'Utiliser différents registres de langage', 'C1,C2');

-- Module: Vocabulaire
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Vocabulaire', 'V-C1', 'Famille, travail, quotidien, vêtements, nourriture, loisirs, sentiments', 'A1'),
('Vocabulaire', 'V-C2', 'Localisation dans le temps et l\'espace, logement, météo, pays/villes, argent, moyens de transports, événements, médias', 'A2,B1'),
('Vocabulaire', 'V-C3', 'Sujets culturels (cinéma, spectacles, littérature, art, ...) sujets d\'actualité et faits de société, le système scolaire, les événements, psychologie, enrichissement lexical (synonyme, antonyme, polysémie)', 'B1,B2'),
('Vocabulaire', 'V-C4', 'Expressions idiomatiques, proverbes, faux amis', 'C1,C2');

-- Module: Au quotidien
INSERT INTO referentiel (module, code, contenu, niveaux) VALUES
('Au Quotidien', 'A-C1', 'Animaux', NULL),
('Au Quotidien', 'A-C2', 'Météo', NULL),
('Au Quotidien', 'A-C3', 'L\'heure', NULL),
('Au Quotidien', 'A-C4', 'Noël', NULL),
('Au Quotidien', 'A-C5', 'Halloween', NULL);
