-- ============================================================
--  SMART CITY COMPLAINT & CIVIC ISSUE TRACKING SYSTEM
--  Run this in phpMyAdmin > smart_city_db > SQL tab
-- ============================================================

USE smart_city_v2;
-- ── 1. TABLES ───────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS Municipality (
    municipality_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS Department (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    head_name VARCHAR(100),
    contact_email VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS Ward (
    ward_id INT PRIMARY KEY AUTO_INCREMENT,
    area_name VARCHAR(100) NOT NULL,
    population INT,
    municipality_id INT NOT NULL,
    FOREIGN KEY (municipality_id) REFERENCES Municipality(municipality_id)
);

CREATE TABLE IF NOT EXISTS Category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    type VARCHAR(50) NOT NULL,
    priority_level VARCHAR(20),
    avg_resolution_days INT
);

CREATE TABLE IF NOT EXISTS Citizen (
    citizen_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    cnic VARCHAR(15) UNIQUE NOT NULL,
    phone VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    address TEXT,
    password VARCHAR(255),
    registered_date DATE DEFAULT (CURRENT_DATE)
);

CREATE TABLE IF NOT EXISTS Officer (
    officer_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50),
    contact VARCHAR(15),
    password VARCHAR(255),
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES Department(department_id)
);

CREATE TABLE IF NOT EXISTS Location (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    address TEXT NOT NULL,
    latitude FLOAT,
    longitude FLOAT,
    ward_id INT NOT NULL,
    FOREIGN KEY (ward_id) REFERENCES Ward(ward_id)
);

CREATE TABLE IF NOT EXISTS Complaint (
    complaint_id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT NOT NULL,
    submitted_date DATE DEFAULT (CURRENT_DATE),
    status VARCHAR(30) DEFAULT 'submitted',
    citizen_id INT NOT NULL,
    category_id INT NOT NULL,
    location_id INT NOT NULL,
    assigned_dept_id INT,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id),
    FOREIGN KEY (category_id) REFERENCES Category(category_id),
    FOREIGN KEY (location_id) REFERENCES Location(location_id),
    FOREIGN KEY (assigned_dept_id) REFERENCES Department(department_id)
);

CREATE TABLE IF NOT EXISTS Evidence_Media (
    media_id INT PRIMARY KEY AUTO_INCREMENT,
    file_path VARCHAR(255),
    type VARCHAR(20),
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    complaint_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Status_Log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    old_status VARCHAR(30),
    new_status VARCHAR(30),
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    complaint_id INT NOT NULL,
    officer_id INT,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id),
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id)
);

CREATE TABLE IF NOT EXISTS Resolution (
    resolution_id INT PRIMARY KEY AUTO_INCREMENT,
    action_taken TEXT,
    resolved_date DATE,
    quality_check VARCHAR(20),
    complaint_id INT NOT NULL,
    officer_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id),
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id)
);

CREATE TABLE IF NOT EXISTS Inspection (
    inspection_id INT PRIMARY KEY AUTO_INCREMENT,
    report TEXT,
    inspection_date DATE,
    result VARCHAR(30),
    complaint_id INT NOT NULL,
    officer_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id),
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id)
);

CREATE TABLE IF NOT EXISTS Escalation (
    escalation_id INT PRIMARY KEY AUTO_INCREMENT,
    level INT,
    reason TEXT,
    escalated_date DATE DEFAULT (CURRENT_DATE),
    escalated_to VARCHAR(100),
    complaint_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Budget_Allocation (
    budget_id INT PRIMARY KEY AUTO_INCREMENT,
    amount FLOAT,
    spent FLOAT DEFAULT 0,
    fiscal_year VARCHAR(10),
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES Department(department_id)
);

CREATE TABLE IF NOT EXISTS Contractor (
    contractor_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(15),
    specialization VARCHAR(100),
    rating FLOAT DEFAULT 0
);

CREATE TABLE IF NOT EXISTS Fine (
    fine_id INT PRIMARY KEY AUTO_INCREMENT,
    amount FLOAT,
    reason TEXT,
    issued_date DATE DEFAULT (CURRENT_DATE),
    paid_status VARCHAR(20) DEFAULT 'unpaid',
    complaint_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Notification (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read VARCHAR(5) DEFAULT 'false',
    channel VARCHAR(20) DEFAULT 'app',
    citizen_id INT NOT NULL,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id)
);

CREATE TABLE IF NOT EXISTS Feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    citizen_rating INT CHECK (citizen_rating BETWEEN 1 AND 5),
    comments TEXT,
    submitted_date DATE DEFAULT (CURRENT_DATE),
    complaint_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Survey (
    survey_id INT PRIMARY KEY AUTO_INCREMENT,
    question TEXT,
    response TEXT,
    response_date DATE DEFAULT (CURRENT_DATE),
    citizen_id INT NOT NULL,
    FOREIGN KEY (citizen_id) REFERENCES Citizen(citizen_id)
);

CREATE TABLE IF NOT EXISTS Appeal (
    appeal_id INT PRIMARY KEY AUTO_INCREMENT,
    reason TEXT,
    status VARCHAR(30) DEFAULT 'pending',
    filed_date DATE DEFAULT (CURRENT_DATE),
    decision_date DATE,
    complaint_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Work_Order (
    work_order_id INT PRIMARY KEY AUTO_INCREMENT,
    description TEXT,
    issued_date DATE DEFAULT (CURRENT_DATE),
    status VARCHAR(30) DEFAULT 'pending',
    complaint_id INT NOT NULL,
    contractor_id INT NOT NULL,
    officer_id INT NOT NULL,
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id),
    FOREIGN KEY (contractor_id) REFERENCES Contractor(contractor_id),
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id)
);

CREATE TABLE IF NOT EXISTS Officer_Assignment (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    assigned_date DATE DEFAULT (CURRENT_DATE),
    status VARCHAR(30) DEFAULT 'active',
    officer_id INT NOT NULL,
    complaint_id INT NOT NULL,
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id),
    FOREIGN KEY (complaint_id) REFERENCES Complaint(complaint_id)
);

CREATE TABLE IF NOT EXISTS Audit_Log (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(100),
    table_affected VARCHAR(50),
    action_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    officer_id INT,
    FOREIGN KEY (officer_id) REFERENCES Officer(officer_id)
);

CREATE TABLE IF NOT EXISTS Announcement (
    announcement_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200),
    content TEXT,
    published_date DATE DEFAULT (CURRENT_DATE),
    department_id INT NOT NULL,
    municipality_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES Department(department_id),
    FOREIGN KEY (municipality_id) REFERENCES Municipality(municipality_id)
);

CREATE TABLE IF NOT EXISTS Report (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200),
    report_type VARCHAR(50),
    generated_date DATE DEFAULT (CURRENT_DATE),
    summary TEXT,
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES Department(department_id)
);

-- ── 2. TRIGGERS ─────────────────────────────────────────────

DELIMITER $$

CREATE TRIGGER trg_log_new_complaint
AFTER INSERT ON Complaint
FOR EACH ROW
BEGIN
    INSERT INTO Status_Log (old_status, new_status, complaint_id)
    VALUES ('none', 'submitted', NEW.complaint_id);
END$$

CREATE TRIGGER trg_status_change
AFTER UPDATE ON Complaint
FOR EACH ROW
BEGIN
    IF OLD.status <> NEW.status THEN
        INSERT INTO Status_Log (old_status, new_status, complaint_id)
        VALUES (OLD.status, NEW.status, NEW.complaint_id);
    END IF;
END$$

CREATE TRIGGER trg_notify_on_resolve
AFTER UPDATE ON Complaint
FOR EACH ROW
BEGIN
    IF NEW.status = 'resolved' AND OLD.status <> 'resolved' THEN
        INSERT INTO Notification (message, citizen_id, channel)
        VALUES (
            CONCAT('Your complaint #', NEW.complaint_id, ' has been resolved. Thank you!'),
            NEW.citizen_id, 'app'
        );
    END IF;
END$$

CREATE TRIGGER trg_audit_resolution
AFTER INSERT ON Resolution
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Log (action, table_affected, officer_id)
    VALUES (CONCAT('Resolution recorded for complaint #', NEW.complaint_id), 'Resolution', NEW.officer_id);
END$$

DELIMITER ;

-- ── 3. VIEWS ────────────────────────────────────────────────

CREATE OR REPLACE VIEW CitizenView AS
SELECT citizen_id, name, phone, email, address, registered_date FROM Citizen;

CREATE OR REPLACE VIEW DepartmentDashboard AS
SELECT d.department_id, d.name AS department,
    COUNT(c.complaint_id) AS total_complaints,
    SUM(CASE WHEN c.status='resolved'    THEN 1 ELSE 0 END) AS resolved,
    SUM(CASE WHEN c.status='in_progress' THEN 1 ELSE 0 END) AS in_progress,
    SUM(CASE WHEN c.status='submitted'   THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN c.status='escalated'   THEN 1 ELSE 0 END) AS escalated
FROM Department d
LEFT JOIN Complaint c ON d.department_id = c.assigned_dept_id
GROUP BY d.department_id, d.name;

CREATE OR REPLACE VIEW OfficerWorkload AS
SELECT o.officer_id, o.name AS officer_name, d.name AS department,
    COUNT(oa.complaint_id) AS assigned_complaints,
    SUM(CASE WHEN c.status='resolved' THEN 1 ELSE 0 END) AS resolved_count
FROM Officer o
JOIN Department d ON o.department_id = d.department_id
LEFT JOIN Officer_Assignment oa ON o.officer_id = oa.officer_id
LEFT JOIN Complaint c ON oa.complaint_id = c.complaint_id
GROUP BY o.officer_id, o.name, d.name;

CREATE OR REPLACE VIEW HighIssueWards AS
SELECT w.ward_id, w.area_name, m.name AS municipality, COUNT(c.complaint_id) AS total_complaints
FROM Ward w
JOIN Municipality m ON w.municipality_id = m.municipality_id
JOIN Location l ON w.ward_id = l.ward_id
JOIN Complaint c ON l.location_id = c.location_id
GROUP BY w.ward_id, w.area_name, m.name
ORDER BY total_complaints DESC;

-- ── 4. SAMPLE DATA ───────────────────────────────────────────

INSERT INTO Municipality (name, city, province) VALUES
('Karachi Metropolitan Corporation', 'Karachi', 'Sindh'),
('Lahore Metropolitan Corporation', 'Lahore', 'Punjab');

INSERT INTO Department (name, head_name, contact_email) VALUES
('Roads & Infrastructure', 'Ahmed Khan', 'roads@kmc.gov.pk'),
('Water & Sanitation', 'Sara Ali', 'water@kmc.gov.pk'),
('Electricity & Power', 'Usman Raza', 'electricity@kmc.gov.pk'),
('Sanitation & Waste', 'Fatima Malik', 'sanitation@kmc.gov.pk');

INSERT INTO Ward (area_name, population, municipality_id) VALUES
('Gulshan-e-Iqbal', 450000, 1),
('North Nazimabad', 380000, 1),
('DHA Phase 5', 290000, 1),
('Clifton', 210000, 1);

INSERT INTO Category (type, priority_level, avg_resolution_days) VALUES
('Road Damage', 'High', 7),
('Water Leakage', 'High', 5),
('Electricity Outage', 'Critical', 2),
('Sanitation Issue', 'Medium', 10),
('Street Light', 'Low', 14);

-- Password for all sample citizens/officers is: password123
INSERT INTO Citizen (name, cnic, phone, email, address, password) VALUES
('Ali Hassan',    '42101-1234567-1', '0300-1234567', 'ali@gmail.com',    'House 5, Block A, Gulshan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Sara Ahmed',    '42101-2345678-2', '0301-2345678', 'sara@gmail.com',   'Flat 3, North Nazimabad',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Kamran Malik',  '42101-3456789-3', '0302-3456789', 'kamran@gmail.com', 'Street 7, DHA Phase 5',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Ayesha Raza',   '42101-4567890-4', '0303-4567890', 'ayesha@gmail.com', 'Clifton Block 8',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Bilal Khan',    '42101-5678901-5', '0304-5678901', 'bilal@gmail.com',  'Gulshan Block 13',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO Officer (name, role, contact, password, department_id) VALUES
('Inspector Tariq',  'Field Inspector',  '0311-1111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1),
('Engineer Nasir',   'Senior Engineer',  '0311-2222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2),
('Technician Adeel', 'Electrician',      '0311-3333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3),
('Supervisor Hina',  'Area Supervisor',  '0311-4444444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 4),
('Inspector Zara',   'Field Inspector',  '0311-5555555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

INSERT INTO Location (address, latitude, longitude, ward_id) VALUES
('Main University Road, Gulshan', 24.9333, 67.1167, 1),
('Block H, North Nazimabad',      24.9500, 67.0500, 2),
('Khayaban-e-Ittehad, DHA',       24.8167, 67.0667, 3),
('Clifton Sea View Road',          24.8300, 67.0200, 4),
('Gulshan Chowrangi',              24.9200, 67.1000, 1);

INSERT INTO Complaint (description, status, citizen_id, category_id, location_id, assigned_dept_id) VALUES
('Large pothole on main road causing accidents',          'submitted',   1, 1, 1, 1),
('Water pipe burst flooding the street for 3 days',       'in_progress', 2, 2, 2, 2),
('Power outage in entire block since yesterday',          'resolved',    3, 3, 3, 3),
('Garbage not collected for 2 weeks, health hazard',      'submitted',   4, 4, 4, 4),
('Street lights not working on main road',                'in_progress', 5, 5, 5, 1),
('Road completely broken after rain, cars stuck',         'escalated',   1, 1, 5, 1),
('Sewage water overflowing into homes',                   'submitted',   2, 4, 2, 4),
('Water supply stopped for 5 days',                       'in_progress', 3, 2, 3, 2);

INSERT INTO Officer_Assignment (assigned_date, status, officer_id, complaint_id) VALUES
('2024-12-09', 'active',    1, 1),
('2024-12-10', 'active',    2, 2),
('2024-12-11', 'completed', 3, 3),
('2024-12-12', 'active',    4, 4),
('2024-12-13', 'active',    5, 5);

INSERT INTO Inspection (report, inspection_date, result, complaint_id, officer_id) VALUES
('Pothole confirmed - 2m diameter, dangerous',   '2024-12-10', 'action_required', 1, 1),
('Pipe burst confirmed at junction B7',           '2024-12-11', 'action_required', 2, 2),
('Power line fault at transformer',               '2024-12-12', 'resolved',        3, 3);

INSERT INTO Resolution (action_taken, resolved_date, quality_check, complaint_id, officer_id) VALUES
('Power line repaired, transformer replaced', '2024-12-13', 'passed', 3, 3);

INSERT INTO Escalation (level, reason, escalated_date, escalated_to, complaint_id) VALUES
(2, 'Unresolved for over 30 days', '2024-12-15', 'Department Head', 6);

INSERT INTO Contractor (name, contact, specialization, rating) VALUES
('BuildRight Pvt Ltd',  '021-35001234', 'Road Construction',    3.8),
('AquaFix Solutions',   '021-35005678', 'Plumbing & Water',     4.2),
('PowerTech Services',  '021-35009012', 'Electrical Works',     4.5);

INSERT INTO Budget_Allocation (amount, spent, fiscal_year, department_id) VALUES
(5000000, 1200000, '2024-25', 1),
(3000000,  800000, '2024-25', 2),
(4000000,  500000, '2024-25', 3),
(2000000,  300000, '2024-25', 4);

INSERT INTO Feedback (citizen_rating, comments, complaint_id) VALUES
(5, 'Issue resolved quickly, very satisfied!', 3);

INSERT INTO Notification (message, is_read, channel, citizen_id) VALUES
('Your complaint #1 has been received.',             'true',  'app', 1),
('Your complaint #2 is now in progress.',            'false', 'SMS', 2),
('Your complaint #3 has been resolved. Thank you!',  'true',  'app', 3);

INSERT INTO Announcement (title, content, published_date, department_id, municipality_id) VALUES
('Road Maintenance Dec 2024', 'Maintenance on University Road Dec 25-30. Use alternate routes.', '2024-12-20', 1, 1),
('Water Supply Interruption',  'Water supply off in North Nazimabad on Dec 28 for pipe work.',   '2024-12-22', 2, 1);

INSERT INTO Report (title, report_type, generated_date, summary, department_id) VALUES
('December 2024 Roads Report',  'monthly',    '2024-12-31', 'Total: 45, Resolved: 38, Pending: 7',    1),
('Q4 2024 Water Report',        'quarterly',  '2024-12-31', 'Total: 62, Resolved: 55, Escalated: 3',  2);

SELECT 'Database setup complete! All 25 tables, triggers, views and sample data loaded.' AS Status;