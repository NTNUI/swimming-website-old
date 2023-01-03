from datetime import date
import unittest
from matplotlib.font_manager import json_dump
import requests
import random
import json
import faker


class TestUrlRewrite(unittest.TestCase):

    BASE = "https://127.0.0.1/api/member/"

    def test_get_all_users(self):
        response = requests.get(self.BASE, verify=False)
        self.assertEqual(response.status_code, 200)

    def test_registration(self):
        fake = faker.Faker("no")
        profile = fake.simple_profile()
        profile["birth_date"] = profile["birthdate"].isoformat()
        profile["phone"] = ("+47" + fake.phone_number()).replace(" ", "")
        profile["gender"] = profile["sex"]
        profile["zip"] = profile["address"].split(", ")[1][0:4]
        profile["address"] = profile["address"].split(", ")[0]
        profile["email"] = profile["mail"]
        del profile["sex"]
        del profile["birthdate"]
        del profile["mail"]
        del profile["username"]

        # Register new user
        profile_json = json.dumps(profile)
        response = requests.post(self.BASE, profile_json, verify=False)
        self.assertEqual(response.status_code, 200)

        # check that user have been registered
        response = requests.get(self.BASE, verify=False)
        self.assertEqual(response.status_code, 200)

        found = False
        for member in response.json():
            if profile["name"] == member["name"]:
                found = True
                break

        self.assertEqual(found, True)

    def test_volunteering(self):
        response = requests.get(self.BASE, verify=False)
        self.assertEqual(response.status_code, 200)

        # pick a random member
        member = random.choice(response.json())
        member_id = member["id"]

        # set volunteering approved on member
        member["have_volunteered"] = "1"

        # update member
        member_json = json.dumps(member)
        response = requests.patch(
            self.BASE + str(member_id), member_json, verify=False)

        # check that member have been updated
        response = requests.get(self.BASE + str(member_id), verify=False)
        self.assertEqual(response.status_code, 200)
        print(response.json())


if __name__ == '__main__':
    unittest.main()
