#!/bin/bash

#Simple script to:
# - fix permissions inside zenario_custom directory

echo "Starting zenario_fix_perms.sh"

echo "This fixes permissions in cache, public and private folders, and the editable_css folders and files in each skin."

SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"

cd $SCRIPT_DIR
cd ../..

for mydir in "cache" "public" "private"
do

  if [ ! -d "$mydir" ]; then
    echo "Creating $mydir ..."
    mkdir $mydir
    echo "...done."
  fi

  if [ -d "$mydir" ]; then
    perm=$(stat -c %a "$mydir")
    if [ ! "$perm" = "777" ]; then
      echo "Doing chmod 777 $mydir ..."
      chmod 777 $mydir
      echo "...done."
    fi
  fi

done

if [ ! -d "zenario_custom" ]; then
  echo "Please make an empty directory with the name zenario_custom."
else
  cd zenario_custom

  if [ ! -d "skins" ]; then
    echo "Creating zenario_custom/skins ..."
    mkdir skins
    echo "...done."
  fi

  cd skins

  for d in `find . -name  editable_css`
  do
   echo "Doing chmod 777 $d ..."
   chmod 777 $d
   echo "...done."
   echo "Doing chmod 666 $d/*.css ..."
   chmod 666 $d/*.css
   echo "...done."
  done

fi
