# School Management System — Entity Relationship Diagram

> Generated: 2026-07-08 | Database: `school_management` | Tables: 16

```mermaid
erDiagram
    USERS {
        int id PK
        varchar username UK
        varchar email UK
        varchar password
        enum role
        enum status
        timestamp created_at
        timestamp updated_at
    }

    SETTINGS {
        varchar setting_key PK
        text setting_value
    }

    CLASSES {
        int id PK
        varchar class_name
        varchar section
        varchar academic_year
        timestamp created_at
    }

    STUDENTS {
        int id PK
        int user_id FK
        varchar admission_number UK
        varchar student_id_card UK
        varchar first_name
        varchar last_name
        enum gender
        date date_of_birth
        varchar nationality
        varchar blood_group
        varchar phone
        varchar email
        text address
        varchar parent_name
        varchar parent_phone
        varchar parent_email
        int class_id FK
        varchar roll_number
        varchar academic_year
        enum status
        varchar photo
        timestamp created_at
        timestamp updated_at
    }

    TEACHERS {
        int id PK
        int user_id FK
        varchar employee_id UK
        varchar full_name
        enum gender
        varchar phone
        varchar email
        text address
        varchar qualification
        decimal salary
        date date_joined
        enum status
        varchar photo
        timestamp created_at
        timestamp updated_at
    }

    SUBJECTS {
        int id PK
        varchar subject_name
        varchar subject_code
        int teacher_id FK
        int class_id FK
        timestamp created_at
    }

    TIMETABLES {
        int id PK
        int class_id FK
        int subject_id FK
        enum day_of_week
        time start_time
        time end_time
        varchar classroom
    }

    STUDENT_ATTENDANCE {
        int id PK
        int student_id FK
        date date
        enum status
        text remarks
        timestamp created_at
    }

    TEACHER_ATTENDANCE {
        int id PK
        int teacher_id FK
        date date
        enum status
        text remarks
        timestamp created_at
    }

    EXAMS {
        int id PK
        varchar exam_name
        int class_id FK
        date exam_date
        varchar academic_year
        timestamp created_at
    }

    MARKS {
        int id PK
        int exam_id FK
        int student_id FK
        int subject_id FK
        decimal marks_obtained
        decimal max_marks
        text remarks
        timestamp created_at
    }

    FEES {
        int id PK
        int student_id FK
        varchar fee_type
        decimal amount
        date due_date
        varchar academic_year
        enum status
        timestamp created_at
    }

    FEE_PAYMENTS {
        int id PK
        int fee_id FK
        decimal amount_paid
        timestamp payment_date
        enum payment_method
        varchar transaction_reference
        varchar receipt_number UK
        timestamp created_at
    }

    BOOKS {
        int id PK
        varchar title
        varchar author
        varchar isbn UK
        int quantity
        int available_quantity
        varchar category
        varchar shelf_location
    }

    BOOK_BORROWS {
        int id PK
        int book_id FK
        int student_id FK
        date borrow_date
        date due_date
        date return_date
        enum status
        decimal fine_amount
        boolean fine_paid
        timestamp created_at
    }

    ANNOUNCEMENTS {
        int id PK
        varchar title
        text content
        enum target_role
        timestamp created_at
    }

    %% Relationships
    USERS ||--o| STUDENTS : "has one"
    USERS ||--o| TEACHERS : "has one"

    CLASSES ||--o{ STUDENTS : "enrolls"
    CLASSES ||--o{ SUBJECTS : "offers"
    CLASSES ||--o{ TIMETABLES : "schedules"
    CLASSES ||--o{ EXAMS : "sits"

    TEACHERS ||--o{ SUBJECTS : "teaches"
    TEACHERS ||--o{ TEACHER_ATTENDANCE : "logs"

    SUBJECTS ||--o{ TIMETABLES : "assigned to"
    SUBJECTS ||--o{ MARKS : "graded in"

    STUDENTS ||--o{ STUDENT_ATTENDANCE : "logs"
    STUDENTS ||--o{ MARKS : "receives"
    STUDENTS ||--o{ FEES : "billed"
    STUDENTS ||--o{ BOOK_BORROWS : "borrows"

    EXAMS ||--o{ MARKS : "contains"

    FEES ||--o{ FEE_PAYMENTS : "paid via"

    BOOKS ||--o{ BOOK_BORROWS : "lent in"
```

## Key Relationships Summary

| Relationship | Type | Notes |
|---|---|---|
| Users → Students | One-to-One | CASCADE delete |
| Users → Teachers | One-to-One | CASCADE delete |
| Classes → Students | One-to-Many | SET NULL on class delete |
| Classes → Subjects | One-to-Many | CASCADE delete |
| Classes → Exams | One-to-Many | CASCADE delete |
| Teachers → Subjects | One-to-Many | SET NULL on teacher delete |
| Subjects → Marks | One-to-Many | CASCADE delete |
| Students → Marks | One-to-Many | CASCADE delete |
| Exams → Marks | One-to-Many | CASCADE delete |
| Fees → FeePayments | One-to-Many | CASCADE delete |
| Books → BookBorrows | One-to-Many | CASCADE delete |

## Unique Constraints

| Table | Unique Key |
|---|---|
| users | username, email |
| students | admission_number, student_id_card |
| teachers | employee_id |
| classes | (class_name, section, academic_year) |
| subjects | (subject_code, class_id) |
| student_attendance | (student_id, date) |
| teacher_attendance | (teacher_id, date) |
| marks | (exam_id, student_id, subject_id) |
| fee_payments | receipt_number |
| books | isbn |
