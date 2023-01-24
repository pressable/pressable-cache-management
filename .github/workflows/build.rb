#!/usr/bin/env ruby

require 'octokit'

# Read the version from the main file.
puts "Getting version from #{ENV['VERSION_FILE_PATH']}"
version_line = File.foreach(ENV['VERSION_FILE_PATH']).grep(/Version:\s*\K\S+/)

# Pulled out the specific version number.
version = version_line.first.split('Version:')[1].strip

puts "Version: #{version}"

raise 'Version not found in main PHP file' unless version

# Get connection to Github.
client = Octokit::Client.new(access_token: ENV['GITHUB_TOKEN'])

release_name = "Release #{version}"
tag_name = version.to_s

# Create the Release, using the release_name.
puts "Creating tagged release: #{release_name}"
release = client.create_release(
  ENV['REPO_NAME'],
  tag_name,
  {
    name: release_name,
    target_commitish: ENV['REPO_SHA'],
    draft: false,
    prerelease: false
  }
)
puts 'Done creating tagged release'

# Upload the zip to the release asset.
puts 'Uploading zip to assets'
client.upload_asset(
  release[:url],
  ENV['ZIP_FILE_NAME'],
  {
    content_type: 'application/zip',
    name: ENV['PROJECT_ZIP_NAME']
  }
)
puts 'Done uploading zip to assets'
