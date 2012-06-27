<?php

namespace Model;

class CommentMediaHashNotFound extends \CommentMediaNotFound {}
class CommentMediaDirNotAvailable extends \CommentMediaNotFound {}
class CommentMediaFileNotFound extends \CommentMediaNotFound {}
class CommentMediaHidden extends \CommentMediaNotFound {}
class CommentMediaHiddenDay extends \CommentMediaNotFound {}

class CommentDeleteWrongPass extends \Fuel_Exception {}
class CommentMediaNotFound extends \Fuel_Exception {}