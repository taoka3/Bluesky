<?php
class Bluesky
{
    public $jwt;
    public $handle;

    public function __construct(string $handle, string $password)
    {
        $this->handle = $handle;
        $this->jwt = $this->getJwt($handle, $password);
    }

    /**
     * @param string $handle
     * @param string $password
     * @return mixed
     */
    private function getJwt(string $handle, string $password): mixed
    {
        $ch = curl_init("https://bsky.social/xrpc/com.atproto.server.createSession");
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            CURLOPT_POSTFIELDS => json_encode([
                "identifier" => $handle,
                "password" => $password,
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseJson = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($responseJson["accessJwt"])) {
                return $responseJson["accessJwt"];
            } else {
                throw new \Exception("Failed to obtain JWT: " . $response);
            }
        } else {
            throw new \Exception('Failed ' . json_last_error_msg());
        }
    }

    /**
     * @param string $text
     * @param string|null $imagePath
     * @param string|null $link
     * @param array $tags
     * @return mixed
     */
    public function webPost(
        string $text,
        string|null $imagePath = null,
        string|null $link = null,
        array $tags = []
    ): mixed {
        $imageUri = $imagePath ? $this->uploadImage($imagePath) : null;
        $facets = [];
        if (count($tags)) {
            $text = $text . ' ' . implode(' ', $tags);
        }
        $record = [
            "\$type" => "app.bsky.feed.post",
            "text" => $text,
            "createdAt" => $this->getNowTime(),
        ];

        if ($imageUri && $link) {
            $record['embed'] = [
                "\$type" => "app.bsky.embed.external",
                "external" => [
                    "uri" => $link,
                    "title" => $text,
                    "description" => $text,
                    "thumb" => $imageUri
                ]
            ];
        }

        if (count($tags)) {

            foreach ($tags as $tag) {
                $byteStart = strpos($text, $tag);
                $byteEnd = $byteStart + strlen($tag);
                $facets[] = [
                    'index' => [
                        'byteStart' => $byteStart,
                        'byteEnd' => $byteEnd
                    ],
                    'features' => [
                        [
                            '$type' => 'app.bsky.richtext.facet#tag',
                            'tag' => ltrim($tag, '#')
                        ]
                    ]
                ];
            }
        }

        if (count($facets)) {
            $record['facets'] = $facets;
        }

        $ch = curl_init("https://bsky.social/xrpc/com.atproto.repo.createRecord");
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->jwt}",
            ],
            CURLOPT_POSTFIELDS => json_encode([
                "repo" => $this->handle,
                "collection" => "app.bsky.feed.post",
                "record" => $record,
            ]),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * @param string $text
     * @param string|null $imagePath
     * @param string|null $link
     * @param array $tags
     * @return mixed
     */
    public function post(
        string $text,
        string|null $imagePath = null,
        string|null $link = null,
        array $tags = []
    ): mixed {
        $facets = [];
        $imageUri = $imagePath ? $this->uploadImage($imagePath) : null;
        if (count($tags)) {
            $text = $text . ' ' . implode(' ', $tags);
        }


        $record = [
            "\$type" => "app.bsky.feed.post",
            "text" => $text,
            "createdAt" => $this->getNowTime(),
        ];

        if ($imageUri) {
            $record['embed'] = [
                '$type' => 'app.bsky.embed.images',
                'images' => [
                    [
                        'image' => $imageUri,
                        'alt' => 'Image description'
                    ]
                ]
            ];
        }

        if ($link) {
            $linkStart = strpos($text, $link);
            $linkEnd = $linkStart + strlen($link);

            $facets[] = [
                'index' => [
                    'byteStart' => $linkStart,
                    'byteEnd' => $linkEnd
                ],
                'features' => [
                    [
                        '$type' => 'app.bsky.richtext.facet#link',
                        'uri' => $link
                    ]
                ]
            ];
        }

        if (count($tags)) {

            foreach ($tags as $tag) {
                $byteStart = strpos($text, $tag);
                $byteEnd = $byteStart + strlen($tag);
                $facets[] = [
                    'index' => [
                        'byteStart' => $byteStart,
                        'byteEnd' => $byteEnd,
                    ],
                    'features' => [
                        [
                            '$type' => 'app.bsky.richtext.facet#tag',
                            'tag' => ltrim($tag, '#')
                        ]
                    ]
                ];
            }
        }

        if (count($facets)) {
            $record['facets'] = $facets;
        }



        $ch = curl_init("https://bsky.social/xrpc/com.atproto.repo.createRecord");
        curl_setopt_array($ch, [
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Authorization: Bearer {$this->jwt}",
            ],
            CURLOPT_POSTFIELDS => json_encode([
                "repo" => $this->handle,
                "collection" => "app.bsky.feed.post",
                "record" => $record,
            ]),
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * @param string $imagePath
     * @return mixed
     */
    private function uploadImage(string $imagePath): mixed
    {
        try {
            $imageData = file_get_contents($imagePath);
            $mime = mime_content_type($imagePath);

            $ch = curl_init("https://bsky.social/xrpc/com.atproto.repo.uploadBlob");
            curl_setopt_array($ch, [
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    "Content-Type: $mime",
                    "Authorization: Bearer {$this->jwt}",
                ],
                CURLOPT_POSTFIELDS => $imageData,
            ]);

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpcode == 200) {
                $responseJson = json_decode($response, true);
                return $responseJson['blob'] ?? null;
            } else {
                throw new \Exception("Failed to upload image: HTTP $httpcode - $response");
            }
        } catch (\Throwable $th) {
            throw new \Exception('Failed ' . $th->getMessage());
        }
    }

    /**
     * @param string $timeZone
     * @return string
     */
    private function getNowTime(string $timeZone = 'Asia/Tokyo'): string
    {
        $dt = new DateTime('now', new DateTimeZone($timeZone));
        return $dt->format('c');
    }
}