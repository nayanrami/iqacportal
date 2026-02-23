-- Remove duplicate courses while keeping the one with the lowest ID
DELETE c1 FROM courses c1
INNER JOIN courses c2 
WHERE 
    c1.id > c2.id AND 
    c1.code = c2.code AND 
    c1.department_id = c2.department_id AND
    COALESCE(c1.semester, 0) = COALESCE(c2.semester, 0);
