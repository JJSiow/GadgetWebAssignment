import random

names = [
    "Alice Johnson", "Bob Smith", "Chloe Kim", "David Lee", "Emma Wilson",
    "Frank Brown", "Grace Hall", "Henry Davis", "Isabella Moore", "Jack White"
]
streets = ["Elm Street", "Oak Avenue", "Pine Road", "Maple Lane", "Birch Boulevard", 
           "Cedar Circle", "Walnut Way", "Chestnut Drive", "Ash Trail", "Palm Street"]
cities = ["Springfield", "Lincoln", "New York", "Boston", "Chicago", 
          "Miami", "Seattle", "Denver", "Austin", "San Francisco"]

with open("diverse_insert_members.sql", "w") as file:
    for i in range(1, 101):
        member_id = f"M{i:05d}"
        name = random.choice(names)
        phone_no = ''.join([str(random.randint(0, 9)) for _ in range(10)])
        gender = random.choice(["M", "F"])
        email = f"{name.lower().replace(' ', '.')}@example.com"
        password = f"{name.split()[0]}{random.randint(100, 999)}!"
        address = f"{random.randint(1, 999)} {random.choice(streets)}, {random.choice(cities)}"
        status = random.choice(["Active", "Disabled"])
        is_login = random.choice(["Y", "N"])
        
        file.write(
            f"INSERT INTO member VALUES ('{member_id}', '{name}', '{phone_no}', '{gender}', "
            f"'{email}', '{password}', '{address}', NULL, '{status}', '{is_login}');\n"
        )
