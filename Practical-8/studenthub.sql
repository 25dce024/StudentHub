CREATE DATABASE IF NOT EXISTS studenthub;

USE studenthub;

CREATE TABLE students (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20),
    mobile VARCHAR(15),
    college VARCHAR(150),
    branch VARCHAR(50),
    address VARCHAR(200),
    hobbies VARCHAR(200)
);

INSERT INTO students
(id, name, gender, mobile, college, branch, address, hobbies)
VALUES
(1, 'Princy', 'Female', '9876543210',
 'ABC College of Engineering', 'CE',
 'Anand, Gujarat, India', 'Reading, Music'),

(2, 'Dhruvi', 'Female', '9876543211',
 'XYZ College of Engineering', 'CE',
 'Surat, Gujarat, India', 'Music, Traveling'),

(3, 'Mahek', 'Female', '9876543212',
 'ABC Institute of Technology', 'IT',
 'Valsad, Gujarat, India', 'Reading, Gaming'),

(4, 'Kavya', 'Female', '9876543213',
 'XYZ Institute of Technology', 'IT',
 'Anand, Gujarat, India', 'Sports, Traveling');


CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    event_date DATE,
    venue VARCHAR(100),
    description TEXT
);

INSERT INTO events (title, event_date, venue, description)
VALUES
('Hackathon 2026', '2026-08-15', 'Innovation Lab',
 'A 24-hour coding competition where students solve real-world problems.'),

('AI Workshop', '2026-08-20', 'Seminar Hall',
 'Learn the basics of Artificial Intelligence, Machine Learning and Generative AI.'),

('Career Guidance Seminar', '2026-08-25', 'Auditorium',
 'Industry experts will guide students about internships and placements.');


CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    event_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (event_id) REFERENCES events(id)
);
