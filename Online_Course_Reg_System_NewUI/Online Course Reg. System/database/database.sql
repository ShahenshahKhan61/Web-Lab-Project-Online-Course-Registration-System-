 sql
-- =========================================================
-- UniCourse - Online Course Registration System
-- MySQL Database
-- =========================================================

CREATE DATABASE online_course_registration;

USE online_course_registration;


-- =========================================================
-- STUDENTS TABLE
-- =========================================================

CREATE TABLE students (
    student_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL
);


-- =========================================================
-- TEACHERS TABLE
-- =========================================================

CREATE TABLE teachers (
    teacher_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL
);


-- =========================================================
-- COORDINATORS TABLE
-- =========================================================

CREATE TABLE coordinators (
    coordinator_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL
);

