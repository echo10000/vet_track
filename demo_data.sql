-- demo_data.sql
-- Extra demo records for showing VetTrack CRUD, search, and filters.
-- Safe to run more than once: users are inserted by unique email, and demo animals are
-- only added when a matching name does not already exist for the same owner.

USE vettrack_db;

INSERT IGNORE INTO users (name, email, password, role) VALUES
('Dr. Sofia Mendoza', 'staff3@vettrack.com', '$2y$12$NAw/SLAHBsHIPP8T41vEuevNKOtXqDBZVULhYEc/ub.5CA3gbZAmm', 'staff'),
('Mika Tan', 'owner4@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner'),
('Paolo Lim', 'owner5@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner'),
('Jessa Villanueva', 'owner6@vettrack.com', '$2y$12$njrVPX2O1yHfxotmQVtzv.qV7cVyYrh7tJXCyq4o4ljU3G5pqUJmC', 'owner');

INSERT INTO owners (user_id, phone, address)
SELECT u.id, '09561234567', 'Talamban, Cebu City'
FROM users u
WHERE u.email = 'owner4@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM owners o WHERE o.user_id = u.id);

INSERT INTO owners (user_id, phone, address)
SELECT u.id, '09671234567', 'Banilad, Mandaue City'
FROM users u
WHERE u.email = 'owner5@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM owners o WHERE o.user_id = u.id);

INSERT INTO owners (user_id, phone, address)
SELECT u.id, '09781234567', 'Pajo, Lapu-Lapu City'
FROM users u
WHERE u.email = 'owner6@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM owners o WHERE o.user_id = u.id);

INSERT INTO animals (owner_id, name, species, breed, age, weight)
SELECT o.id, 'Coco', 'dog', 'Shih Tzu', 2.50, 6.40
FROM owners o
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner4@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM animals a WHERE a.owner_id = o.id AND a.name = 'Coco');

INSERT INTO animals (owner_id, name, species, breed, age, weight)
SELECT o.id, 'Luna', 'cat', 'Persian', 1.75, 3.90
FROM owners o
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner4@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM animals a WHERE a.owner_id = o.id AND a.name = 'Luna');

INSERT INTO animals (owner_id, name, species, breed, age, weight)
SELECT o.id, 'Rio', 'bird', 'Lovebird', 0.80, 0.06
FROM owners o
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner5@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM animals a WHERE a.owner_id = o.id AND a.name = 'Rio');

INSERT INTO animals (owner_id, name, species, breed, age, weight)
SELECT o.id, 'Mocha', 'rabbit', 'Mini Rex', 1.20, 1.80
FROM owners o
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner5@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM animals a WHERE a.owner_id = o.id AND a.name = 'Mocha');

INSERT INTO animals (owner_id, name, species, breed, age, weight)
SELECT o.id, 'Shadow', 'dog', 'Labrador Mix', 5.00, 24.00
FROM owners o
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner6@vettrack.com'
  AND NOT EXISTS (SELECT 1 FROM animals a WHERE a.owner_id = o.id AND a.name = 'Shadow');

INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
SELECT a.id, s.id, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '08:30:00', 'Search demo: Coco vaccination booster', 'pending'
FROM animals a
INNER JOIN owners o ON a.owner_id = o.id
INNER JOIN users u ON o.user_id = u.id
LEFT JOIN users s ON s.email = 'staff1@vettrack.com'
WHERE u.email = 'owner4@vettrack.com'
  AND a.name = 'Coco'
  AND NOT EXISTS (
      SELECT 1 FROM appointments ap
      WHERE ap.animal_id = a.id
        AND ap.reason = 'Search demo: Coco vaccination booster'
  );

INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
SELECT a.id, s.id, DATE_ADD(CURDATE(), INTERVAL 4 DAY), '16:00:00', 'Dental check and cleaning estimate', 'confirmed'
FROM animals a
INNER JOIN owners o ON a.owner_id = o.id
INNER JOIN users u ON o.user_id = u.id
LEFT JOIN users s ON s.email = 'staff3@vettrack.com'
WHERE u.email = 'owner4@vettrack.com'
  AND a.name = 'Luna'
  AND NOT EXISTS (
      SELECT 1 FROM appointments ap
      WHERE ap.animal_id = a.id
        AND ap.reason = 'Dental check and cleaning estimate'
  );

INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
SELECT a.id, s.id, DATE_SUB(CURDATE(), INTERVAL 3 DAY), '10:00:00', 'Beak and feather condition review', 'done'
FROM animals a
INNER JOIN owners o ON a.owner_id = o.id
INNER JOIN users u ON o.user_id = u.id
LEFT JOIN users s ON s.email = 'staff2@vettrack.com'
WHERE u.email = 'owner5@vettrack.com'
  AND a.name = 'Rio'
  AND NOT EXISTS (
      SELECT 1 FROM appointments ap
      WHERE ap.animal_id = a.id
        AND ap.reason = 'Beak and feather condition review'
  );

INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
SELECT a.id, NULL, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '13:30:00', 'New appetite concern', 'pending'
FROM animals a
INNER JOIN owners o ON a.owner_id = o.id
INNER JOIN users u ON o.user_id = u.id
WHERE u.email = 'owner5@vettrack.com'
  AND a.name = 'Mocha'
  AND NOT EXISTS (
      SELECT 1 FROM appointments ap
      WHERE ap.animal_id = a.id
        AND ap.reason = 'New appetite concern'
  );

INSERT INTO appointments (animal_id, staff_id, date, time, reason, status)
SELECT a.id, s.id, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '11:30:00', 'Follow-up for limping', 'cancelled'
FROM animals a
INNER JOIN owners o ON a.owner_id = o.id
INNER JOIN users u ON o.user_id = u.id
LEFT JOIN users s ON s.email = 'staff1@vettrack.com'
WHERE u.email = 'owner6@vettrack.com'
  AND a.name = 'Shadow'
  AND NOT EXISTS (
      SELECT 1 FROM appointments ap
      WHERE ap.animal_id = a.id
        AND ap.reason = 'Follow-up for limping'
  );

INSERT INTO health_records (animal_id, appointment_id, diagnosis, treatment, notes, recorded_by)
SELECT a.id, ap.id, 'Mild tartar buildup', 'Dental cleaning recommended', 'Use this record to demo update and delete actions.', s.id
FROM animals a
INNER JOIN appointments ap ON ap.animal_id = a.id AND ap.reason = 'Dental check and cleaning estimate'
INNER JOIN users s ON s.email = 'staff3@vettrack.com'
WHERE a.name = 'Luna'
  AND NOT EXISTS (
      SELECT 1 FROM health_records hr
      WHERE hr.animal_id = a.id
        AND hr.diagnosis = 'Mild tartar buildup'
  );

INSERT INTO health_records (animal_id, appointment_id, diagnosis, treatment, notes, recorded_by)
SELECT a.id, ap.id, 'Healthy feather condition', 'Routine observation only', 'Good demo record for completed appointment history.', s.id
FROM animals a
INNER JOIN appointments ap ON ap.animal_id = a.id AND ap.reason = 'Beak and feather condition review'
INNER JOIN users s ON s.email = 'staff2@vettrack.com'
WHERE a.name = 'Rio'
  AND NOT EXISTS (
      SELECT 1 FROM health_records hr
      WHERE hr.animal_id = a.id
        AND hr.diagnosis = 'Healthy feather condition'
  );

INSERT INTO health_records (animal_id, appointment_id, diagnosis, treatment, notes, recorded_by)
SELECT a.id, NULL, 'Weight monitoring needed', 'Adjust meal portions and recheck weight next visit', 'Standalone record with no linked appointment.', s.id
FROM animals a
INNER JOIN users s ON s.email = 'staff1@vettrack.com'
WHERE a.name = 'Shadow'
  AND NOT EXISTS (
      SELECT 1 FROM health_records hr
      WHERE hr.animal_id = a.id
        AND hr.diagnosis = 'Weight monitoring needed'
  );
