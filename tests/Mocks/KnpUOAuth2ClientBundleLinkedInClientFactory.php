<?php

namespace App\Tests\Mocks;

use KnpU\OAuth2ClientBundle\Client\Provider\LinkedInClient;
use League\OAuth2\Client\Provider\LinkedInResourceOwner;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\TestCase;

readonly class KnpUOAuth2ClientBundleLinkedInClientFactory
{
    public static function createMock()
    {
        $g = new Generator();
        $m = $g->getMock(
            LinkedInClient::class,
            ['fetchUser'],
            [],
            '',
            false
        );

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
          "email": "thirdparty.linkedin.user@example.com"
        }
        EOT;

        $response = json_decode($json, true);
        $resourceOwner = new LinkedInResourceOwner($response);

        $m
            ->expects(TestCase::once())
            ->method('fetchUser')
            ->with()
            ->willReturn($resourceOwner);

        return $m;
    }
}
