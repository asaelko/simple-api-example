<?php

namespace AppBundle\Service\CDN;

use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\ObjectUploader;
use Aws\S3\S3Client;
use CarlBundle\Exception\RestException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SelectelCDNUploader
{
    /**
     * @var S3Client
     */
    private S3Client $s3;

    public const PUBLIC_BUCKET = 'cdn';
    public const PRIVATE_BUCKET = "private-cdn";

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->s3 = new S3Client([
            'version'                 => 'latest',
            'region'                  => 'ru-1a',
            'endpoint'                => 'https://s3.selcdn.ru',
            'use_path_style_endpoint' => true,
            'credentials'             => [
                'key'    => $parameterBag->get('cdn_api_key'),
                'secret' => $parameterBag->get('cdn_secret_key'),
            ],
        ]);
    }

    /**
     * Загружаем небольшой файл потоком в хранилище
     *
     * @param $source
     * @param string $uuid
     * @param string $filename
     * @param string $mime
     * @return bool
     */
    public function upload($source, string $uuid, string $filename, string $mime): bool
    {
        $uploader = new ObjectUploader(
            $this->s3,
            self::PUBLIC_BUCKET,
            'uploads/' . $uuid . '/' . $filename,
            $source,
            'public-read',
            ['params' => ['ContentType' => $mime]]
        );

        try {
            /** @var Result $response */
            $response = $uploader->upload();

            if ($response->get('ObjectURL')) {
                return true;
            }

            return false;
        } catch (S3Exception $ex) {
            return false;
        }
    }

    /**
     * @param $source
     * @param string $filename
     * @param string $mime
     * @return bool
     */
    public function uploadToPrivateBucket($source, string $filename, string $mime): bool
    {
        $uploader = new ObjectUploader(
            $this->s3,
            self::PRIVATE_BUCKET,
            $filename,
            $source,
            'private',
            ['params' => ['ContentType' => $mime]]
        );

        try {
            /** @var Result $response */
            $response = $uploader->upload();

            if ($response->get('ObjectURL')) {
                return true;
            }

            return false;
        } catch (S3Exception $ex) {
            return false;
        }
    }

    /**
     * @param string $bucket
     * @param string $path
     * @return mixed
     * @throws RestException
     */
    public function downloadFile(string $bucket, string $path)
    {
        $file = $this->s3->getObject(['Bucket' => $bucket, 'Key' => $path]);

        if (!$file->hasKey('Body')) {
            throw new RestException('Не удалось скачать файл');
        }

        return $file->get('Body')->getContents();
    }
}