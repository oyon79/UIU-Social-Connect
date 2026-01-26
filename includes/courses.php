<?php
/**
 * Course Data for UIU - CSE and EEE Departments
 * Organized by Department and Trimester
 */

// CSE Department Courses
$CSE_COURSES = [
    1 => [
        ['code' => 'ENG 1011', 'title' => 'English – I'],
        ['code' => 'BDS 1201', 'title' => 'History of the Emergence of Bangladesh'],
        ['code' => 'CSE 1110', 'title' => 'Introduction to Computer Systems'],
        ['code' => 'MATH 1151', 'title' => 'Fundamental Calculus']
    ],
    2 => [
        ['code' => 'ENG 1013', 'title' => 'English – II'],
        ['code' => 'CSE 1111', 'title' => 'Structured Programming Language'],
        ['code' => 'CSE 1112', 'title' => 'Structured Programming Language Lab'],
        ['code' => 'CSE 2213', 'title' => 'Discrete Mathematics']
    ],
    3 => [
        ['code' => 'MATH 2183', 'title' => 'Calculus & Linear Algebra'],
        ['code' => 'PHY 2105', 'title' => 'Physics'],
        ['code' => 'PHY 2106', 'title' => 'Physics Lab'],
        ['code' => 'CSE 2215', 'title' => 'Data Structure & Algorithms – I'],
        ['code' => 'CSE 2216', 'title' => 'DSA – I Lab']
    ],
    4 => [
        ['code' => 'MATH 2201', 'title' => 'Coordinate Geometry & Vector Analysis'],
        ['code' => 'CSE 1325', 'title' => 'Digital Logic Design'],
        ['code' => 'CSE 1326', 'title' => 'DLD Lab'],
        ['code' => 'CSE 1115', 'title' => 'Object Oriented Programming'],
        ['code' => 'CSE 1116', 'title' => 'OOP Lab']
    ],
    5 => [
        ['code' => 'MATH 2205', 'title' => 'Probability & Statistics'],
        ['code' => 'SOC 2101', 'title' => 'Society & Engineering Ethics'],
        ['code' => 'CSE 2217', 'title' => 'Data Structure & Algorithms – II'],
        ['code' => 'CSE 2218', 'title' => 'DSA – II Lab'],
        ['code' => 'EEE 2113', 'title' => 'Electrical Circuits']
    ],
    6 => [
        ['code' => 'CSE 3521', 'title' => 'Database Management Systems'],
        ['code' => 'CSE 3522', 'title' => 'DBMS Lab'],
        ['code' => 'EEE 2123', 'title' => 'Electronics'],
        ['code' => 'EEE 2124', 'title' => 'Electronics Lab'],
        ['code' => 'CSE 4165', 'title' => 'Web Programming']
    ],
    7 => [
        ['code' => 'CSE 3313', 'title' => 'Computer Architecture'],
        ['code' => 'CSE 2118', 'title' => 'Advanced OOP Lab'],
        ['code' => 'BIO 3105', 'title' => 'Biology for Engineers'],
        ['code' => 'CSE 3411', 'title' => 'System Analysis & Design'],
        ['code' => 'CSE 3412', 'title' => 'SAD Lab']
    ],
    8 => [
        ['code' => 'CSE 4325', 'title' => 'Microprocessors & Microcontrollers'],
        ['code' => 'CSE 4326', 'title' => 'Microprocessors Lab'],
        ['code' => 'CSE 3421', 'title' => 'Software Engineering'],
        ['code' => 'CSE 3422', 'title' => 'SE Lab'],
        ['code' => 'CSE 3811', 'title' => 'Artificial Intelligence'],
        ['code' => 'CSE 3812', 'title' => 'AI Lab']
    ],
    9 => [
        ['code' => 'CSE 2233', 'title' => 'Theory of Computation'],
        ['code' => 'GED OPT1', 'title' => 'General Education Optional – I'],
        ['code' => 'PMG 4101', 'title' => 'Project Management'],
        ['code' => 'CSE 3711', 'title' => 'Computer Networks'],
        ['code' => 'CSE 3712', 'title' => 'CN Lab']
    ],
    10 => [
        ['code' => 'GED OPT2', 'title' => 'General Education Optional – II'],
        ['code' => 'CSE 4000A', 'title' => 'Final Year Design Project – I'],
        ['code' => 'CSE Elective – I', 'title' => 'Elective Course'],
        ['code' => 'CSE 4509', 'title' => 'Operating Systems'],
        ['code' => 'CSE 4510', 'title' => 'OS Lab']
    ],
    11 => [
        ['code' => 'GED OPT3', 'title' => 'General Education Optional – III'],
        ['code' => 'CSE Elective – II', 'title' => 'Elective Course'],
        ['code' => 'CSE Elective – III', 'title' => 'Elective Course'],
        ['code' => 'CSE 4000B', 'title' => 'Final Year Design Project – II'],
        ['code' => 'CSE 4531', 'title' => 'Computer Security']
    ],
    12 => [
        ['code' => 'CSE 4000C', 'title' => 'Final Year Design Project – III'],
        ['code' => 'EEE 4261', 'title' => 'Green Computing'],
        ['code' => 'CSE Elective – IV', 'title' => 'Elective Course'],
        ['code' => 'CSE Elective – V', 'title' => 'Elective Course']
    ]
];

// EEE Department Courses
$EEE_COURSES = [
    1 => [
        ['code' => 'ENG 1011', 'title' => 'English – I'],
        ['code' => 'MAT 1101', 'title' => 'Calculus I'],
        ['code' => 'EEE 1001', 'title' => 'Electrical Circuits I'],
        ['code' => 'BDS 1201', 'title' => 'History of the Emergence of Bangladesh']
    ],
    2 => [
        ['code' => 'ENG 1013', 'title' => 'English – II'],
        ['code' => 'MAT 1103', 'title' => 'Calculus II'],
        ['code' => 'EEE 1003', 'title' => 'Electrical Circuits II'],
        ['code' => 'EEE 1004', 'title' => 'Electrical Circuits Laboratory'],
        ['code' => 'PHY 1101', 'title' => 'Physics I']
    ],
    3 => [
        ['code' => 'EEE 2000', 'title' => 'Simulation Laboratory'],
        ['code' => 'EEE 2101', 'title' => 'Electronics I'],
        ['code' => 'PHY 1103', 'title' => 'Physics II'],
        ['code' => 'PHY 1104', 'title' => 'Physics Laboratory'],
        ['code' => 'MAT 2105', 'title' => 'Linear Algebra & Differential Equations']
    ],
    4 => [
        ['code' => 'EEE 2103', 'title' => 'Electronics II'],
        ['code' => 'EEE 2104', 'title' => 'Electronics Laboratory'],
        ['code' => 'CHE 2101', 'title' => 'Chemistry'],
        ['code' => 'CHE 2102', 'title' => 'Chemistry Laboratory'],
        ['code' => 'MAT 2107', 'title' => 'Complex Variables, Fourier & Laplace Transforms']
    ],
    5 => [
        ['code' => 'MAT 2109', 'title' => 'Coordinate Geometry & Vector Analysis'],
        ['code' => 'EEE 2401', 'title' => 'Structured Programming Language'],
        ['code' => 'EEE 2402', 'title' => 'Structured Programming Language Laboratory'],
        ['code' => 'GED OPT', 'title' => 'General Education Optional'],
        ['code' => 'EEE 2301', 'title' => 'Signals & Linear Systems']
    ],
    6 => [
        ['code' => 'EEE 2200', 'title' => 'Electrical Wiring & Drafting'],
        ['code' => 'EEE 2201', 'title' => 'Energy Conversion I'],
        ['code' => 'EEE 2105', 'title' => 'Digital Electronics'],
        ['code' => 'EEE 2106', 'title' => 'Digital Electronics Laboratory'],
        ['code' => 'EEE 3303', 'title' => 'Probability, Statistics & Random Variables'],
        ['code' => 'EEE 3107', 'title' => 'Electrical Properties of Materials']
    ],
    7 => [
        ['code' => 'ACT 3101', 'title' => 'Financial & Managerial Accounting'],
        ['code' => 'EEE 2203', 'title' => 'Energy Conversion II'],
        ['code' => 'EEE 2204', 'title' => 'Energy Conversion Laboratory'],
        ['code' => 'EEE 3309', 'title' => 'Digital Signal Processing'],
        ['code' => 'EEE 3310', 'title' => 'Digital Signal Processing Laboratory']
    ],
    8 => [
        ['code' => 'SOC 3101', 'title' => 'Society, Environment & Engineering Ethics'],
        ['code' => 'EEE 3305', 'title' => 'Engineering Electromagnetics'],
        ['code' => 'EEE 3307', 'title' => 'Communication Theory'],
        ['code' => 'EEE 3308', 'title' => 'Communication Laboratory'],
        ['code' => 'EEE 3400', 'title' => 'Numerical Techniques Laboratory']
    ],
    9 => [
        ['code' => 'EEE 3205', 'title' => 'Power System'],
        ['code' => 'EEE 3206', 'title' => 'Power System Laboratory'],
        ['code' => 'EEE 3403', 'title' => 'Microprocessor & Interfacing'],
        ['code' => 'EEE 3404', 'title' => 'Microprocessor & Interfacing Laboratory'],
        ['code' => 'EEE 3207', 'title' => 'Power Electronics'],
        ['code' => 'EEE 3208', 'title' => 'Power Electronics Laboratory']
    ],
    10 => [
        ['code' => 'IPE 4101', 'title' => 'Industrial Production Engineering'],
        ['code' => 'EEE 4109', 'title' => 'Control System'],
        ['code' => 'EEE 4110', 'title' => 'Control System Laboratory'],
        ['code' => 'EEE Elective – I (Major)', 'title' => 'Elective Course'],
        ['code' => 'EEE 4901', 'title' => 'Capstone Project – I']
    ],
    11 => [
        ['code' => 'EEE Elective – I (Major)', 'title' => 'Elective Course'],
        ['code' => 'EEE Elective – II (Major)', 'title' => 'Elective Course'],
        ['code' => 'EEE Elective – II Lab', 'title' => 'Elective Laboratory'],
        ['code' => 'EEE Elective – I (Minor)', 'title' => 'Elective Course'],
        ['code' => 'EEE 4902', 'title' => 'Capstone Project – II']
    ],
    12 => [
        ['code' => 'EEE Elective – II (Minor)', 'title' => 'Elective Course'],
        ['code' => 'EEE Elective – II Lab (Minor)', 'title' => 'Elective Laboratory'],
        ['code' => 'GED 4000', 'title' => 'Entrepreneurship & Career'],
        ['code' => 'EEE 4903', 'title' => 'Capstone Project – III'],
        ['code' => 'EEE 4904 / 4905', 'title' => 'Internship / Industrial Training']
    ]
];

/**
 * Get courses for a specific department and trimester
 * 
 * @param string $department Department code (CSE or EEE)
 * @param int $trimester Trimester number (1-12)
 * @return array Array of courses
 */
function getCoursesByTrimester($department, $trimester) {
    global $CSE_COURSES, $EEE_COURSES;
    
    $trimester = (int)$trimester;
    
    if ($department === 'CSE' && isset($CSE_COURSES[$trimester])) {
        return $CSE_COURSES[$trimester];
    } elseif ($department === 'EEE' && isset($EEE_COURSES[$trimester])) {
        return $EEE_COURSES[$trimester];
    }
    
    return [];
}

/**
 * Get all trimesters with courses for a department
 * 
 * @param string $department Department code (CSE or EEE)
 * @return array Array of trimester numbers
 */
function getAvailableTrimesters($department) {
    global $CSE_COURSES, $EEE_COURSES;
    
    if ($department === 'CSE') {
        return array_keys($CSE_COURSES);
    } elseif ($department === 'EEE') {
        return array_keys($EEE_COURSES);
    }
    
    return [];
}
