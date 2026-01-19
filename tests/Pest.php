<?php

// Featureディレクトリ配下のテストで TestCase クラスの機能を使えるようにする
pest()->extend(Tests\TestCase::class)->in('Feature');