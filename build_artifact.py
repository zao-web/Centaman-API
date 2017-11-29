#!/usr/bin/env python
import datetime
import os
import tarfile
import sys

import boto3

org_name, repo_name = os.getenv('TRAVIS_REPO_SLUG').split('/')
AWS_BUCKET = os.getenv('AWS_BUCKET')
BUILD_DIR = os.getenv('TRAVIS_BUILD_DIR')
BUILD_ID = datetime.datetime.now().strftime('%Y%m%d-%H%M%S')
branch = sys.argv[1]


def create_artifact(output_filename, source_dir):
    os.chdir('..')

    with tarfile.open(output_filename, 'w') as tar:
        tar.add(source_dir, arcname=os.path.basename(source_dir))

    artifact_path = os.path.join(os.getcwd(), output_filename)
    print('Artifact created:', artifact_path)
    return artifact_path


def upload_to_s3(artifact_path):
    artifact_filename = os.path.basename(artifact_path)
    print('Uploading to s3://%s/%s/%s' % (AWS_BUCKET, repo_name, artifact_filename))
    s3 = boto3.client('s3')
    s3.upload_file(artifact_path, AWS_BUCKET, '/'.join((repo_name, artifact_filename)))


artifact_path = create_artifact('{}_{}_{}.tar'.format(repo_name, branch, BUILD_ID), BUILD_DIR)
upload_to_s3(artifact_path)
