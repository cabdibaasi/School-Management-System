# School Management System — Data Flow Diagram (DFD)

## Level 0 — Context Diagram

```mermaid
flowchart LR
    Admin(["👤 Admin"])
    Teacher(["👨‍🏫 Teacher"])
    Student(["🎓 Student"])

    SMS["🏫 EduPortal\nSchool Management\nSystem"]

    Admin -- "Manage students, teachers,\nfees, exams, settings" --> SMS
    Teacher -- "Record marks,\nattendance, borrows" --> SMS
    Student -- "View results, fees,\nattendance, timetable" --> SMS

    SMS -- "Reports, invoices,\nreceipts, timetables" --> Admin
    SMS -- "Class schedules,\nmark confirmations" --> Teacher
    SMS -- "Report cards,\nfee invoices, library status" --> Student
```

---

## Level 1 — Main Process Decomposition

```mermaid
flowchart TD
    %% External Entities
    ADMIN(["Admin"])
    TEACHER(["Teacher"])
    STUDENT(["Student"])

    %% Level-1 Processes
    P1["1.0\nAuthentication\n& Sessions"]
    P2["2.0\nStudent & Teacher\nManagement"]
    P3["3.0\nAcademic Scheduling\n(Timetable + Subjects)"]
    P4["4.0\nAttendance\nTracking"]
    P5["5.0\nExam & Marks\nManagement"]
    P6["6.0\nFee & Payment\nProcessing"]
    P7["7.0\nLibrary\nManagement"]
    P8["8.0\nReports &\nAnalytics"]
    P9["9.0\nSettings &\nConfiguration"]

    %% Data Stores
    DS1[("DB: users")]
    DS2[("DB: students\nteachers")]
    DS3[("DB: classes\nsubjects\ntimetables")]
    DS4[("DB: student_attendance\nteacher_attendance")]
    DS5[("DB: exams\nmarks")]
    DS6[("DB: fees\nfee_payments")]
    DS7[("DB: books\nbook_borrows")]
    DS8[("DB: settings\nannouncements")]

    %% Authentication flows
    ADMIN -- credentials --> P1
    TEACHER -- credentials --> P1
    STUDENT -- credentials --> P1
    P1 <--> DS1

    %% Admin drives management
    ADMIN -- student/teacher data --> P2
    P2 <--> DS2

    %% Admin configures schedules
    ADMIN -- class/subject/timetable data --> P3
    P3 <--> DS3

    %% Attendance marking
    ADMIN & TEACHER -- attendance records --> P4
    P4 <--> DS4
    STUDENT -- views own --> P4

    %% Exams and marks
    ADMIN -- exam schedules --> P5
    TEACHER -- mark scores --> P5
    P5 <--> DS5
    STUDENT -- views report card --> P5

    %% Fees
    ADMIN -- invoice / payment --> P6
    P6 <--> DS6
    STUDENT -- views own fees --> P6

    %% Library
    ADMIN & TEACHER -- book issue/return --> P7
    P7 <--> DS7
    STUDENT -- views borrows --> P7

    %% Reports
    ADMIN -- filter params --> P8
    P8 --> DS2
    P8 --> DS4
    P8 --> DS5
    P8 --> DS6

    %% Settings
    ADMIN -- settings updates --> P9
    P9 <--> DS8
```

---

## Level 2 — Process 5.0: Exam & Marks Management

```mermaid
flowchart TD
    ADMIN(["Admin"])
    TEACHER(["Teacher"])
    STUDENT(["Student"])

    P51["5.1\nSchedule Exam"]
    P52["5.2\nLoad Student\nRoster"]
    P53["5.3\nRecord / Update\nMarks"]
    P54["5.4\nCalculate Grade\n& GPA"]
    P55["5.5\nGenerate\nReport Card"]

    DS_EXAMS[("exams")]
    DS_MARKS[("marks")]
    DS_SUBJECTS[("subjects")]
    DS_STUDENTS[("students")]

    ADMIN -- exam name, class, date --> P51
    P51 --> DS_EXAMS

    TEACHER -- select exam + subject --> P52
    P52 --> DS_STUDENTS
    P52 --> DS_SUBJECTS
    P52 --> DS_MARKS

    TEACHER -- marks & remarks --> P53
    P53 --> DS_MARKS

    DS_MARKS --> P54
    P54 -- grade/GPA/status --> P55

    ADMIN & TEACHER -- select student + exam --> P55
    STUDENT -- view own --> P55
    P55 --> DS_MARKS
    P55 --> DS_STUDENTS
```

---

## Level 2 — Process 6.0: Fee & Payment Processing

```mermaid
flowchart TD
    ADMIN(["Admin"])
    STUDENT(["Student"])

    P61["6.1\nCreate Fee\nInvoice"]
    P62["6.2\nBulk Generate\nFor Class"]
    P63["6.3\nRecord Payment\n& Generate Receipt"]
    P64["6.4\nUpdate Fee\nStatus"]
    P65["6.5\nView Invoice /\nReceipt"]

    DS_FEES[("fees")]
    DS_PAYMENTS[("fee_payments")]
    DS_STUDENTS[("students")]
    DS_CLASSES[("classes")]

    ADMIN -- student, type, amount --> P61
    P61 --> DS_FEES

    ADMIN -- class, type, amount --> P62
    P62 --> DS_CLASSES
    P62 --> DS_STUDENTS
    P62 --> DS_FEES

    ADMIN -- amount, method --> P63
    P63 --> DS_PAYMENTS
    P63 --> P64
    P64 --> DS_FEES

    ADMIN & STUDENT --> P65
    P65 --> DS_FEES
    P65 --> DS_PAYMENTS
```
