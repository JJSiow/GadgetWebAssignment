import random

names = [
    "Alice Johnson", "Bob Smith", "Chloe Kim", "David Lee", "Emma Wilson",
    "Frank Brown", "Grace Hall", "Henry Davis", "Isabella Moore", "Jack White"
]

with open("admin.sql", "w") as file:
    for i in range(1, 11):
        admin_id = f"A{i:02d}"
        name = random.choice(names)
        phone_no = ''.join([str(random.randint(0, 9)) for _ in range(10)])
        email = f"{name.lower().replace(' ', '.')}@example.com"
        password = f"{name.split()[0]}{random.randint(100, 999)}!"
        is_super_admin = random.choice(["Y", "N"])
        status = random.choice(["Active", "Disabled"])

        file.write(
            f"INSERT INTO admin VALUES ('{admin_id}', '{name}', '{phone_no}', "
            f"'{email}', '{password}', NULL, '{status}' ,'{is_super_admin}');\n"
        )
