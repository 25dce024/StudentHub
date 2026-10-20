-- Practical 11: 21 Realistic Fictional Student Records for Student Management CRUD
USE `studenthub`;

INSERT IGNORE INTO `students` (`id`, `student_id`, `name`, `email`, `course`, `gender`, `mobile`, `college`, `branch`, `semester`, `academic_year`, `cgpa`, `address`, `status`) VALUES
(1, '25DCE007', 'Femi Dungrani', '25dce024@charusat.edu.in', 'Computer Engineering', 'Female', '9876543210', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 9.24, 'Anand, Gujarat', 'active'),
(2, '25DCE001', 'Aarav Patel', 'aarav.patel@charusat.edu.in', 'Computer Engineering', 'Male', '9876543211', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 8.85, 'Ahmedabad, Gujarat', 'active'),
(3, '25DCE002', 'Pranav Shah', 'pranav@charusat.edu.in', 'Computer Engineering', 'Male', '9876543212', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 9.15, 'Vadodara, Gujarat', 'active'),
(4, '25DIT015', 'Prateek Sharma', 'prateek.sharma@charusat.edu.in', 'Information Technology', 'Male', '9876543213', 'DEPSTAR, CHARUSAT', 'IT', 3, '2nd Year', 8.95, 'Surat, Gujarat', 'active'),
(5, '25DCE004', 'Dhruv Vaghani', 'dhruv@charusat.edu.in', 'Computer Engineering', 'Male', '9876543214', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 8.40, 'Rajkot, Gujarat', 'inactive'),
(6, '25DIT022', 'Nikhil Desai', 'nikhil.desai@charusat.edu.in', 'Information Technology', 'Male', '9876543215', 'DEPSTAR, CHARUSAT', 'IT', 3, '2nd Year', 8.70, 'Bhavnagar, Gujarat', 'active'),
(7, '25DAI031', 'Rohan Patel', 'rohan.patel@charusat.edu.in', 'AI & Data Science', 'Male', '9876543216', 'DEPSTAR, CHARUSAT', 'AI & DS', 3, '2nd Year', 9.42, 'Anand, Gujarat', 'active'),
(8, '25DCE008', 'Yash Joshi', 'yash.joshi@charusat.edu.in', 'Computer Engineering', 'Male', '9876543217', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 8.65, 'Nadiad, Gujarat', 'active'),
(9, '25DIT045', 'Harshil Shah', 'harshil.shah@charusat.edu.in', 'Information Technology', 'Male', '9876543218', 'DEPSTAR, CHARUSAT', 'IT', 3, '2nd Year', 8.60, 'Gandhinagar, Gujarat', 'active'),
(10, '25DAI052', 'Ishaan Patel', 'ishaan.patel@charusat.edu.in', 'AI & Data Science', 'Male', '9876543219', 'DEPSTAR, CHARUSAT', 'AI & DS', 3, '2nd Year', 8.10, 'Ahmedabad, Gujarat', 'inactive'),
(11, '25DCE011', 'Pranay Mehta', 'pranay.mehta@charusat.edu.in', 'Computer Engineering', 'Male', '9876543220', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 9.50, 'Vadodara, Gujarat', 'active'),
(12, '25DCE012', 'Karan Dave', 'karan.dave@charusat.edu.in', 'Computer Engineering', 'Male', '9876543221', 'CSPIT, CHARUSAT', 'CE', 5, '3rd Year', 8.35, 'Surat, Gujarat', 'active'),
(13, '25DIT033', 'Rohan Verma', 'rohan.verma@charusat.edu.in', 'Information Technology', 'Male', '9876543222', 'DEPSTAR, CHARUSAT', 'IT', 5, '3rd Year', 8.20, 'Rajkot, Gujarat', 'active'),
(14, '25DEC014', 'Devang Prajapati', 'devang.prajapati@charusat.edu.in', 'Electronics & Comm', 'Male', '9876543223', 'CSPIT, CHARUSAT', 'EC', 3, '2nd Year', 7.85, 'Mehsana, Gujarat', 'active'),
(15, '25DCE015', 'Het Patel', 'het.patel@charusat.edu.in', 'Computer Engineering', 'Male', '9876543224', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 8.75, 'Navsari, Gujarat', 'active'),
(16, '25DCE016', 'Tejas Parekh', 'tejas.parekh@charusat.edu.in', 'Computer Engineering', 'Male', '9876543225', 'CSPIT, CHARUSAT', 'CE', 3, '2nd Year', 8.80, 'Bharuch, Gujarat', 'active'),
(17, '25DME031', 'Mohit Parmar', 'mohit.parmar@charusat.edu.in', 'Mechanical Engineering', 'Male', '9876543226', 'CSPIT, CHARUSAT', 'ME', 3, '2nd Year', 8.05, 'Valsad, Gujarat', 'inactive'),
(18, '25DCL014', 'Jaydeep Vaghela', 'jaydeep.vaghela@charusat.edu.in', 'Civil Engineering', 'Male', '9876543227', 'CSPIT, CHARUSAT', 'CL', 3, '2nd Year', 7.65, 'Patan, Gujarat', 'active'),
(19, '24DCE028', 'Rahul Chauhan', 'rahul.chauhan@charusat.edu.in', 'Computer Engineering', 'Male', '9876543228', 'CSPIT, CHARUSAT', 'CE', 7, '4th Year', 8.90, 'Surendranagar, Gujarat', 'active'),
(20, '25DAI041', 'Bhavya Panchal', 'bhavya.panchal@charusat.edu.in', 'AI & Data Science', 'Male', '9876543229', 'DEPSTAR, CHARUSAT', 'AI & DS', 3, '2nd Year', 9.02, 'Junagadh, Gujarat', 'active'),
(21, '23DIT091', 'Kunal Desai', 'kunal.desai@charusat.edu.in', 'Information Technology', 'Male', '9876543230', 'DEPSTAR, CHARUSAT', 'IT', 7, '4th Year', 9.15, 'Morbi, Gujarat', 'active');
