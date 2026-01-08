INSERT INTO utilisateurs (email, prenom, nom, numlogin, password, role) VALUES
('marie.martin@example.com', 'Marie', 'Martin', 'marie01', SHA2('Marie', 256), 'admin'),
('pierre.dubois@example.com', 'Pierre', 'Dubois', 'pierre01', SHA2('Pierre', 256), 'formateur'),
('sophie.garcia@example.com', 'Sophie', 'Garcia', 'sophie01', SHA2('Sophie', 256), 'stagiaire OP'),
('lucas.lopez@example.com', 'Lucas', 'Lopez', 'lucas01', SHA2('Lucas', 256), 'stagiaire FPC');
