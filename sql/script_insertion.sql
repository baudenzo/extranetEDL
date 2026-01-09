INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role, sexe, photo) VALUES
('marie.martin@example.com', 'Marie', 'Martin', 'marie01', SHA2('Marie', 256), 'admin', 'feminin', 'pp/1.png'),
('pierre.dubois@example.com', 'Pierre', 'Dubois', 'pierre01', SHA2('Pierre', 256), 'formateur', 'masculin', 'pp/2.jpg'),
('sophie.garcia@example.com', 'Sophie', 'Garcia', 'sophie01', SHA2('Sophie', 256), 'stagiaire OP', 'feminin', 'pp/3.png'),
('lucas.lopez@example.com', 'Lucas', 'Lopez', 'lucas01', SHA2('Lucas', 256), 'stagiaire FPC', 'masculin', 'pp/4.jpg');

