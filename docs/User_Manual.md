# School Management System — User Manual

Welcome to the **EduPortal** School Management System. This manual details the features, portals, and modules of the system and how to use them based on your user role: **Administrator**, **Teacher**, or **Student**.

---

## 1. Getting Started

### 1.1 Logging In
1. Open your web browser and navigate to the application URL (e.g., `http://localhost/Talent/`).
2. You will be redirected to the Login Screen.
3. Enter your **Username** or **Email** and your **Password**.
4. Click **Sign In**.

### 1.2 Accessing Your Profile
- Once logged in, click your name or profile picture in the top-right corner and select **My Profile**.
- Here you can view your personal details, change your profile picture (supported formats: JPG, PNG, WEBP), and update your account password.

---

## 2. Administrator Portal

Administrators have full access to all configuration panels, student/teacher enrollment, fees, settings, and analytical reports.

### 2.1 Managing Users, Students, and Teachers
- **User Accounts (`User Accounts` link)**: Create credentials, manage active status (Active/Inactive), or reset password hashes.
- **Students Portal (`Students` link)**:
  - Add Students by inputting personal information, parent contact, class assignment, and uploading their profile photo.
  - View Student Profiles with demographic and academic overviews.
  - Export lists of students directly to CSV formats.
- **Teachers Portal (`Teachers` link)**:
  - Maintain the teacher roster, record qualifications, salary details, and join dates.
  - View contact details and export data to CSV.

### 2.2 Class & Subject Config
- **Classes (`Classes` link)**: Setup class names, sections, and the corresponding academic year (e.g. `Class 10 - A - 2026-2027`).
- **Subjects (`Subjects` link)**: Register subjects, assign their unique codes (e.g. `MATH101`), link them to a Class, and designate the Teacher responsible for grading them.

### 2.3 Timetables & Scheduling
- **Timetables (`Timetables` link)**:
  - Choose a Class and create scheduling slots.
  - Define the Subject, Day of Week, Start/End times, and classroom location.
  - Preventions are in place to stop scheduling overlaps.

### 2.4 Attendance Tracking
- **Teacher Attendance**: Log teacher presence daily (`Attendance -> Teacher Attendance`).
- **Student Attendance**: Admins can oversee student attendance, adjust records, and view historical reports.

### 2.5 Billing & Fee Management
- **Generate Invoices**: Create individual bills or use the **Bulk Generate** option to charge an entire class with a specific fee type (e.g., "Term 1 Tuition", "Library Fee").
- **Record Payments**: When a payment is made, click **Collect** on the invoice list.
  - Input the payment amount (allows partial payments).
  - Select the method (Cash, Bank Transfer, Card, Check).
  - Write down references or transaction IDs.
- **Invoices & Receipts**: View, download, or print invoices and official transaction receipts.

### 2.6 Library Catalog & Fine Management
- **Library Inventory (`Library Books` link)**: Add books with Title, Author, ISBN, quantity, category, and shelf location.
- **Borrows Tracker (`Borrows` page)**:
  - Issue books to students by searching their names and defining due dates.
  - Return books: The system calculates overdue fines ($0.50 per day past the due date).
  - Clear Fines: Process fine payments and mark overdue transactions as Paid.

### 2.7 System Settings
- **School Profile**: Edit school name, email, phone number, address, and upload the official school logo.
- **System Settings**: Set the current academic year, currency format, and default language.

---

## 3. Teacher Portal

Teachers are responsible for marking student attendance, entering examination marks, and checking class schedules.

### 3.1 Marking Class Attendance
1. Click **Student Attendance** in the sidebar.
2. Select your Class, Section, and Date.
3. Check off each student as **Present**, **Absent**, **Late**, or **Excused**.
4. Click **Save Attendance**.

### 3.2 Entering Exam Marks
1. Go to the **Record Marks** section.
2. Select the scheduled Exam, followed by the Subject you teach.
3. The roster will load. Input the marks obtained for each student (checked against maximum marks limit).
4. Save the grades. The system will automatically calculate percentages, passing status, and GPA equivalents.

### 3.3 Timetable & Library Catalog
- **My Timetable**: View your assigned classes, times, and room locations.
- **Library Catalog**: Browse the list of books available in the school library.

---

## 4. Student Portal

Students have read-only access to their personal schedules, attendance records, report cards, fees, and library history.

### 4.1 Viewing Academic Progress
- **My Timetable**: Look up your class schedule.
- **My Attendance**: Check your monthly presence logs and percentages.
- **Report Card**: View details of all completed examinations, subject-wise marks, final grades, and GPA summaries.

### 4.2 Fee Payments
- **My Fees & Payments**: Check all invoices billed to your account.
- Review paid, partially paid, or outstanding balances.
- Print your invoices and receipts for reimbursement or record-keeping.

### 4.3 Borrowed Books
- **Borrowed Books**: Track your library transaction history.
- See which books are currently borrowed, their due dates, and if you have accrued any unpaid fines for overdue returns.
