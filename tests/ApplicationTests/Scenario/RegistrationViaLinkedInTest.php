<?php

namespace App\Tests\ApplicationTests\Scenario;

use App\VideoBasedMarketing\Account\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;


class RegistrationViaLinkedInTest
    extends WebTestCase
{
    public function test(): void
    {
        $client = static::createClient();
        $client->followRedirects();

        /** @var Container $container */
        $container = $client->getContainer();

        $mockedLinkedInClient = $this->createMock(LinkedInClient::class);

        $json = <<<EOT
        {
          "localizedLastName": "Kie\u00dfling",
          "firstName": {
            "localized": {
              "en_US": "Manuel"
            },
            "preferredLocale": {
              "country": "US",
              "language": "en"
            }
          },
          "lastName": {
            "localized": {
              "en_US": "Kie\u00dfling"
            },
            "preferredLocale": {
              "country": "US",
              "language": "en"
            }
          },
          "profilePicture": {
            "displayImage": "urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA",
            "displayImage~": {
              "paging": {
                "count": 10,
                "start": 0,
                "links": []
              },
              "elements": [
                {
                  "artifact": "urn:li:digitalmediaMediaArtifact:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaMediaArtifactClass:profile-displayphoto-shrink_100_100)",
                  "authorizationMethod": "PUBLIC",
                  "data": {
                    "com.linkedin.digitalmedia.mediaartifact.StillImage": {
                      "mediaType": "image\/jpeg",
                      "rawCodecSpec": {
                        "name": "jpeg",
                        "type": "image"
                      },
                      "displaySize": {
                        "width": 100,
                        "uom": "PX",
                        "height": 100
                      },
                      "storageSize": {
                        "width": 100,
                        "height": 100
                      },
                      "storageAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      },
                      "displayAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      }
                    }
                  },
                  "identifiers": [
                    {
                      "identifier": "https:\/\/media.licdn.com\/dms\/image\/C4D03AQEVYZBjemW7SA\/profile-displayphoto-shrink_100_100\/0\/1598104395631?e=1679529600&v=beta&t=b6ywYHwZwDXYzQN6tAPzgtP1VhzwzwCqRFzxaMQniFo",
                      "index": 0,
                      "mediaType": "image\/jpeg",
                      "file": "urn:li:digitalmediaFile:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaArtifactClass:profile-displayphoto-shrink_100_100,0)",
                      "identifierType": "EXTERNAL_URL",
                      "identifierExpiresInSeconds": 1679529600
                    }
                  ]
                },
                {
                  "artifact": "urn:li:digitalmediaMediaArtifact:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaMediaArtifactClass:profile-displayphoto-shrink_200_200)",
                  "authorizationMethod": "PUBLIC",
                  "data": {
                    "com.linkedin.digitalmedia.mediaartifact.StillImage": {
                      "mediaType": "image\/jpeg",
                      "rawCodecSpec": {
                        "name": "jpeg",
                        "type": "image"
                      },
                      "displaySize": {
                        "width": 200,
                        "uom": "PX",
                        "height": 200
                      },
                      "storageSize": {
                        "width": 200,
                        "height": 200
                      },
                      "storageAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      },
                      "displayAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      }
                    }
                  },
                  "identifiers": [
                    {
                      "identifier": "https:\/\/media.licdn.com\/dms\/image\/C4D03AQEVYZBjemW7SA\/profile-displayphoto-shrink_200_200\/0\/1598104395631?e=1679529600&v=beta&t=3fa1sKc564lFy982Zc1umUIq4iARDxrwiF_Ns8SyrnQ",
                      "index": 0,
                      "mediaType": "image\/jpeg",
                      "file": "urn:li:digitalmediaFile:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaArtifactClass:profile-displayphoto-shrink_200_200,0)",
                      "identifierType": "EXTERNAL_URL",
                      "identifierExpiresInSeconds": 1679529600
                    }
                  ]
                },
                {
                  "artifact": "urn:li:digitalmediaMediaArtifact:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaMediaArtifactClass:profile-displayphoto-shrink_400_400)",
                  "authorizationMethod": "PUBLIC",
                  "data": {
                    "com.linkedin.digitalmedia.mediaartifact.StillImage": {
                      "mediaType": "image\/jpeg",
                      "rawCodecSpec": {
                        "name": "jpeg",
                        "type": "image"
                      },
                      "displaySize": {
                        "width": 400,
                        "uom": "PX",
                        "height": 400
                      },
                      "storageSize": {
                        "width": 400,
                        "height": 400
                      },
                      "storageAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      },
                      "displayAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      }
                    }
                  },
                  "identifiers": [
                    {
                      "identifier": "https:\/\/media.licdn.com\/dms\/image\/C4D03AQEVYZBjemW7SA\/profile-displayphoto-shrink_400_400\/0\/1598104395631?e=1679529600&v=beta&t=UMYxGUvLsbYoCcvqv1XdEXGsN7qkxn1bUZtVHEcrmLQ",
                      "index": 0,
                      "mediaType": "image\/jpeg",
                      "file": "urn:li:digitalmediaFile:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaArtifactClass:profile-displayphoto-shrink_400_400,0)",
                      "identifierType": "EXTERNAL_URL",
                      "identifierExpiresInSeconds": 1679529600
                    }
                  ]
                },
                {
                  "artifact": "urn:li:digitalmediaMediaArtifact:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaMediaArtifactClass:profile-displayphoto-shrink_800_800)",
                  "authorizationMethod": "PUBLIC",
                  "data": {
                    "com.linkedin.digitalmedia.mediaartifact.StillImage": {
                      "mediaType": "image\/jpeg",
                      "rawCodecSpec": {
                        "name": "jpeg",
                        "type": "image"
                      },
                      "displaySize": {
                        "width": 800,
                        "uom": "PX",
                        "height": 800
                      },
                      "storageSize": {
                        "width": 800,
                        "height": 800
                      },
                      "storageAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      },
                      "displayAspectRatio": {
                        "widthAspect": 1,
                        "heightAspect": 1,
                        "formatted": "1.00:1.00"
                      }
                    }
                  },
                  "identifiers": [
                    {
                      "identifier": "https:\/\/media.licdn.com\/dms\/image\/C4D03AQEVYZBjemW7SA\/profile-displayphoto-shrink_800_800\/0\/1598104395631?e=1679529600&v=beta&t=ffAq_6Buh0-qsHToNZeV3AcT2yr2oHNmfvUeEVT3c3o",
                      "index": 0,
                      "mediaType": "image\/jpeg",
                      "file": "urn:li:digitalmediaFile:(urn:li:digitalmediaAsset:C4D03AQEVYZBjemW7SA,urn:li:digitalmediaArtifactClass:profile-displayphoto-shrink_800_800,0)",
                      "identifierType": "EXTERNAL_URL",
                      "identifierExpiresInSeconds": 1679529600
                    }
                  ]
                }
              ]
            }
          },
          "id": "9I2nzj-oKR",
          "localizedFirstName": "Manuel",
          "email": "thirdpary.linkedin.user@example.com"
        }
        EOT;

        $response = json_decode($json, true);
        $resourceOwner = new LinkedInResourceOwner($response);

        $mockedLinkedInClient
            ->expects($this->once())
            ->method('fetchUser')
            ->with()
            ->willReturn($resourceOwner);

        $container->set(
            'knpu.oauth2.client.linkedin',
            $mockedLinkedInClient
        );

        $client->request(
            'GET',
            '/account/thirdpartyauth/linkedin/return?code=AQQ4F6tzWOI0f9uPJpI9hunOi3zIiX0A-SyYw5ZcLJvGe21uhkYQZGoQavQjbtGNr7DM7lr7HbzydqOwd2fI-OJ37un8apx8QwQvcwbDQbsokz2VSNciESGH2twb4kwd-SxFpk5VleDWGqnrLlrGNGrZGUU8dtSYLqVw4CFrn3Lg_GxC__eRlQWihA1B8l6qo77n5wIPjFZtrpfEM9o&state=4d4a73452f61cf1ce1879ff95a633436'
        );

        $this->assertResponseStatusCodeSame(200);

        $em = $container->get(EntityManagerInterface::class);
        /** @var User[] $users */
        $users = $em->getRepository(User::class)->findAll();

        $this->assertCount(
            3,
            $users
        );

        $this->assertSame(
            'thirdpary.linkedin.user@example.com',
            $users[2]->getEmail()
        );
    }
}
