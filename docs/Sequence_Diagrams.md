# School Management System — Sequence Diagrams

## Sequence 1: Student Login & Dashboard Load

```mermaid
sequenceDiagram
    actor User as Student / Admin / Teacher
    participant Browser
    participant login.php
    participant Auth
    participant Database
    participant dashboard.php

    User->>Browser: Navigate to login.php
    Browser->>login.php: GET /views/login.php
    login.php-->>Browser: Render login form

    User->>Browser: Enter username + password → Submit
    Browser->>login.php: POST credentials
    login.php->>Auth: check CSRF token
    Auth-->>login.php: token valid ✅

    login.php->>Database: SELECT user WHERE username = ?
    Database-->>login.php: Return user row

    login.php->>Auth: verifyPassword(plain, hash)
    Auth-->>login.php: password match ✅

    login.php->>Auth: setSession(user_id, role, profile_id)
    login.php-->>Browser: redirect → dashboard.php

    Browser->>dashboard.php: GET /views/dashboard.php
    dashboard.php->>Auth: requireRole(['admin','teacher','student'])
    Auth-->>dashboard.php: role authorized ✅

    dashboard.php->>Database: Aggregate counts (students, teachers, exams…)
    Database-->>dashboard.php: Return stats arrays

    dashboard.php-->>Browser: Render dashboard with KPIs + charts
```

---

## Sequence 2: Teacher Records Student Marks

```mermaid
sequenceDiagram
    actor Teacher
    participant marks_entry.php
    participant Auth
    participant Database
    participant Exam

    Teacher->>marks_entry.php: GET ?exam_id=3&subject_id=5
    marks_entry.php->>Auth: requireRole(['admin','teacher'])
    Auth-->>marks_entry.php: authorized ✅

    marks_entry.php->>Database: SELECT subjects WHERE class_id = exam.class_id
    Database-->>marks_entry.php: subject list

    marks_entry.php->>Exam: getSubjectMarks(examId=3, subjectId=5)
    Exam->>Database: JOIN students + LEFT JOIN marks
    Database-->>Exam: students with existing marks (if any)
    Exam-->>marks_entry.php: student roster + marks

    marks_entry.php-->>Teacher: Render score-entry table

    Teacher->>marks_entry.php: POST marks[] array + CSRF token
    marks_entry.php->>Auth: validateCSRFToken(token)
    Auth-->>marks_entry.php: valid ✅

    marks_entry.php->>Exam: saveSubjectMarks(examId, subjectId, marksArray)
    Exam->>Database: BEGIN TRANSACTION
    loop For each student
        Exam->>Database: INSERT … ON DUPLICATE KEY UPDATE
    end
    Database-->>Exam: commit ✅
    Exam-->>marks_entry.php: true

    marks_entry.php-->>Teacher: Flash "Marks saved!" → redirect
```

---

## Sequence 3: Admin Collects Fee Payment & Issues Receipt

```mermaid
sequenceDiagram
    actor Admin
    participant fees_index.php
    participant collect.php
    participant Fee
    participant Database
    participant receipt.php

    Admin->>fees_index.php: View fee invoices list
    fees_index.php->>Fee: getAll(filters)
    Fee->>Database: SELECT fees + SUM(payments)
    Database-->>Fee: fee rows
    Fee-->>fees_index.php: fees array
    fees_index.php-->>Admin: Render table

    Admin->>collect.php: Click "Record Payment" → GET ?id=42
    collect.php->>Fee: getById(42)
    Fee->>Database: SELECT fee JOIN student
    Database-->>Fee: fee + student details
    Fee-->>collect.php: fee record (balance=$250)
    collect.php-->>Admin: Render payment form

    Admin->>collect.php: POST amount=250, method=cash
    collect.php->>Fee: recordPayment(42, 250, 'cash', '')
    Fee->>Database: BEGIN TRANSACTION
    Fee->>Database: INSERT INTO fee_payments (receipt_number='RCP-A1B2C3D4')
    Fee->>Database: SELECT SUM(paid) FROM fee_payments WHERE fee_id=42
    Fee->>Database: UPDATE fees SET status='paid'
    Database-->>Fee: COMMIT ✅
    Fee-->>collect.php: receiptNo = 'RCP-A1B2C3D4'

    collect.php-->>Admin: redirect → receipt.php?receipt=RCP-A1B2C3D4

    Admin->>receipt.php: GET ?receipt=RCP-A1B2C3D4
    receipt.php->>Fee: getByReceipt('RCP-A1B2C3D4')
    Fee->>Database: SELECT payments JOIN fees JOIN student
    Database-->>Fee: full receipt data
    Fee-->>receipt.php: receipt record
    receipt.php-->>Admin: Render printable receipt
```

---

## Sequence 4: Student Views Report Card

```mermaid
sequenceDiagram
    actor Student
    participant my_results.php
    participant report_card.php
    participant Exam
    participant Database

    Student->>my_results.php: GET /views/exams/my_results.php
    my_results.php->>Exam: getAll() + Auth::profileId()
    Exam->>Database: SELECT DISTINCT exams WHERE student has marks
    Database-->>Exam: exam list
    Exam-->>my_results.php: exams array

    loop For each exam
        my_results.php->>Exam: getStudentReport(studentId, examId)
        Exam->>Database: SELECT marks JOIN subjects
        Database-->>Exam: subject marks
        my_results.php->>Exam: calculateGrade(percentage)
        Exam-->>my_results.php: grade, GPA, status
    end

    my_results.php-->>Student: Exam result cards with CGPA

    Student->>report_card.php: Click "View Report Card" → GET ?exam_id=3
    report_card.php->>Exam: getById(3)
    report_card.php->>Exam: getStudentReport(studentId, 3)
    Exam->>Database: SELECT marks + subjects + teacher
    Database-->>Exam: full marks data
    Exam-->>report_card.php: marks list

    loop For each subject mark
        report_card.php->>Exam: calculateGrade(percentage)
        Exam-->>report_card.php: grade info
    end

    report_card.php-->>Student: Render official report card
    Student->>Browser: window.print() → Save as PDF
```

---

## Sequence 5: Librarian Issues & Returns Book

```mermaid
sequenceDiagram
    actor Admin as Admin / Teacher
    participant borrows.php
    participant Book
    participant Database

    Admin->>borrows.php: GET borrows list
    Book->>Database: refreshOverdueStatuses()
    borrows.php->>Book: getAllBorrows(filters)
    Database-->>Book: borrow records
    Book-->>borrows.php: borrows array
    borrows.php-->>Admin: Render list with Issue Book button

    Admin->>borrows.php: POST action=issue, book_id=7, student_id=12, due_days=14
    borrows.php->>Book: issueBook(7, 12, 14)
    Book->>Database: SELECT available_quantity WHERE id=7
    Database-->>Book: available_quantity=3 ✅

    Book->>Database: BEGIN TRANSACTION
    Book->>Database: INSERT INTO book_borrows (borrow_date, due_date='2026-07-22')
    Book->>Database: UPDATE books SET available_quantity = available_quantity - 1
    Database-->>Book: COMMIT ✅
    Book-->>borrows.php: borrowId=45

    borrows.php-->>Admin: Flash "Book issued!" → redirect

    Note over Admin,Database: 14 days later…

    Admin->>borrows.php: POST action=return, borrow_id=45
    borrows.php->>Book: returnBook(45)
    Book->>Database: SELECT borrow WHERE id=45
    Database-->>Book: borrow (due=2026-07-22, status=overdue)
    Book->>Book: calculate overdue days = 2 → fine = $1.00
    Book->>Database: BEGIN TRANSACTION
    Book->>Database: UPDATE book_borrows SET status='returned', fine_amount=1.00
    Book->>Database: UPDATE books SET available_quantity = available_quantity + 1
    Database-->>Book: COMMIT ✅
    Book-->>borrows.php: fine=$1.00, overdue_days=2

    borrows.php-->>Admin: Flash "Returned! Fine: $1.00" → redirect
```
