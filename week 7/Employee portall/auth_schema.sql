USE employee_portal;

ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) NULL AFTER status;

CREATE UNIQUE INDEX IF NOT EXISTS idx_employees_employee_id ON employees (employee_id);
CREATE UNIQUE INDEX IF NOT EXISTS idx_employees_email ON employees (email);
