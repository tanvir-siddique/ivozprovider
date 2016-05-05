CREATE VIEW ast_hints AS SELECT e.number AS exten, CONCAT('company', u.companyId) AS context, CONCAT('PJSIP/', t.name) AS device FROM Users AS u INNER JOIN Terminals AS t ON u.terminalId = t.id INNER JOIN Extensions AS e ON u.extensionId = e.id;