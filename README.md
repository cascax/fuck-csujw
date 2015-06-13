# fuck-csujw

怒艹中南教务网验证码

## 说明

教务网站验证码识别，2000个数据集测试大概有70%的正确率吧。

获取验证码去除背景、噪点、分割，取得四个字符保存为25*25=625阶的向量。这部分是PHP的。

逻辑回归(LogisticRegression)训练36个分类器，用没有优化的梯度下降，这部分是Python的。

##使用

识别验证码

```
require 'RecognizeCode.php';
$recognize = new RecognizeCode();
$code = $recognize->deal('code.gif');
echo $code;
```

自动获取训练集用于优化训练结果

这里是获取100个验证码，自动提交教务验证识别结果，将正确结果保存在./autocode/target.txt。
全部图片转化成数据保存在./autocode/code.gif。（见autocheck.php）
然后你需要人工识别badcode写在target.txt中，然后用于训练。

```
set_time_limit(0);
require 'autocheck.php';

$good = run(100);
$r = new RecognizeCode();
$r->parseImage($good);
$r->parseImage(100-$good, 'badcode');
```

## License

[MIT license](http://opensource.org/licenses/MIT)