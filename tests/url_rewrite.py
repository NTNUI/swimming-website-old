import unittest
import requests

# Low effort test cases for .htaccess rules


class TestUrlRewrite(unittest.TestCase):

    BASE = "https://127.0.0.1/"

    def get_response_and_content(self, input_file: str, url: str) -> tuple[requests.Response, str]:
        file = open(input_file)
        content = file.read()
        file.close()
        response = requests.get(self.BASE + url, verify=False)
        return (response, content)

    def test_access(self):
        # fail if clients cannot read following files
        allow_list = [
            ("../docker/app/conf/robots.txt", "robots.txt"),
            ("../public/clubs.ts", "clubs.ts"),
            ("../docker/app/conf/.well-known/security.txt",
             ".well-known/security.txt"),
        ]

        for test_case in allow_list:
            (content, response) = self.get_response_and_content(
                test_case[0], test_case[1])

            self.assertEqual(response.status_code, 200)
            self.assertEqual(response.text, content)

        # fail if clients can read following files
        deny_list = [
            ("../docker/app/conf/.htaccess", ".htaccess"),
            ("../docker/app/conf/.well-known/.htaccess", ".well-known/.htaccess"),
            ("../.env", ".env"),
            ("../.env.example", ".env.example"),
        ]

        for test_case in deny_list:
            (content, response) = self.get_response_and_content(
                test_case[0], test_case[1])

            self.assertNotEqual(response.text, content)


if __name__ == '__main__':
    unittest.main()
