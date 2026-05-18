-- seed.sql

USE vettrack_db;

INSERT INTO users (id, name, email, password, role) VALUES
(1, 'System Admin', 'admin@vettrack.com', '$2y$12$M/QHHyHf9vqP.BomqDcZ4.AAOQJJbErNKTMpWE4jkklbrlvZA7QcS', 'admin'),
(2, 'Dr. Lina Santos', 'staff1@vettrack.com', '$2y$12$NAw/SLAHBsHIPP8T41vEuevNKOtXqDBZVULhYEc/ub.5CA3gbZAmm', 'staff'),
(3, 'Vet Assistant Marco Cruz', 'staff2@vettrack.com', '$2y$12$NAw/SLAHBsHIPP8T41vEuevNKOtXqDBZVULhYEc/ub.5CA3gbZAmm', 'staff'),
(4, 'Anna Reyes', 'owner1@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner'),
(5, 'Ben Garcia', 'owner2@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner'),
(6, 'Clara Dizon', 'owner3@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner'),
(7, 'Demo Owner', 'sample@vettrack.com', '$2y$10$e7v1NnRGS4Xd8l85UnBORurqrZp0wZkRjKcY0OhQ02BCx4IplnuZu', 'owner');

INSERT INTO owners (id, user_id, phone, address) VALUES
(1, 4, '09171234567', 'Cebu City'),
(2, 5, '09281234567', 'Mandaue City'),
(3, 6, '09391234567', 'Lapu-Lapu City'),
(4, 7, '09451234567', 'Sample City');

INSERT INTO animals (id, owner_id, name, species, breed, age, weight) VALUES
(1, 1, 'Buddy', 'dog', 'Golden Retriever', 3.00, 28.50),
(2, 1, 'Ming', 'cat', 'Siamese', 2.00, 4.20),
(3, 2, 'Tweety', 'bird', 'Parakeet', 1.00, 0.08),
(4, 2, 'Snowball', 'rabbit', 'Netherland Dwarf', 1.50, 1.30),
(5, 3, 'Max', 'dog', 'Aspin', 4.00, 18.75);

INSERT INTO appointments (id, animal_id, staff_id, date, time, reason, status) VALUES
(1, 1, 2, CURDATE(), '09:00:00', 'Annual wellness check', 'confirmed'),
(2, 2, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:30:00', 'Vaccination schedule', 'pending'),
(3, 3, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '14:00:00', 'Wing check', 'done'),
(4, 4, NULL, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '11:00:00', 'Eating less than usual', 'pending'),
(5, 5, 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '15:30:00', 'Skin irritation', 'cancelled'),
(6, 1, 2, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '13:00:00', 'Dental cleaning consultation', 'confirmed');

INSERT INTO health_records (id, animal_id, appointment_id, diagnosis, treatment, notes, recorded_by) VALUES
(1, 1, 1, 'Healthy overall condition', 'Routine vitamins and wellness advice', 'Continue balanced diet and regular exercise.', 2),
(2, 3, 3, 'Minor feather stress', 'Environmental enrichment and observation', 'Follow up if appetite changes.', 2),
(3, 5, 5, 'Mild skin irritation', 'Topical treatment recommended', 'Owner advised to monitor affected area.', 3),
(4, 2, NULL, 'Routine vaccination review', 'Vaccination plan prepared', 'Next vaccine due on scheduled appointment.', 3);
